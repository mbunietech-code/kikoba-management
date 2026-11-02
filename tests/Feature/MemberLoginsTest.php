<?php

namespace Tests\Feature;

use App\Mail\MemberLoginDetails;
use App\Models\Member;
use App\Models\Organization;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class MemberLoginsTest extends TestCase
{
    use RefreshDatabase;

    private Organization $organization;

    private User $admin;

    private Member $member;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        config(['app.key' => 'base64:MTIzNDU2Nzg5MDEyMzQ1Njc4OTAxMjM0NTY3ODkwMTI=']);
        $this->organization = Organization::create(['name' => 'Umoja Group', 'code' => 'UMOJA']);
        Role::findOrCreate('member', 'web');
        $this->admin = User::factory()->create(['organization_id' => $this->organization->id, 'phone' => '+255700000001']);
        foreach (['members.view', 'users.manage', 'settings.view', 'settings.manage'] as $permission) {
            $this->admin->givePermissionTo(Permission::findOrCreate($permission, 'web'));
        }
        $this->member = Member::factory()->create(['organization_id' => $this->organization->id, 'full_name' => 'Asha Juma', 'phone' => '0716123456', 'email' => 'asha@example.test']);
        $this->actingAs($this->admin);
    }

    public function test_messaging_credentials_are_saved_encrypted_and_never_shown(): void
    {
        $this->put(route('admin.settings.messaging'), $this->messaging())->assertSessionHasNoErrors();

        $stored = Setting::where('key', 'beem_secret_key')->value('value');
        $this->assertNotSame('beem-secret', $stored);
        $this->assertStringNotContainsString('beem-secret', $this->get(route('admin.settings.index', ['tab' => 'messaging']))->assertOk()->getContent());

        // a blank secret on the next save keeps the stored one
        $this->put(route('admin.settings.messaging'), [...$this->messaging(), 'beem_secret_key' => '', 'mail_password' => '']);
        $this->assertSame($stored, Setting::where('key', 'beem_secret_key')->value('value'));
    }

    public function test_the_messaging_tab_shows_the_beem_sms_balance(): void
    {
        Http::fake(['*vendors/balance*' => Http::response(['data' => ['credit_balance' => 1250]])]);
        $this->put(route('admin.settings.messaging'), $this->messaging());

        $this->get(route('admin.settings.index', ['tab' => 'messaging']))->assertOk()
            ->assertSee(t('messaging.smsBalance'))->assertSee('1,250');
        Http::assertSent(fn ($request) => $request->hasHeader('Authorization', 'Basic '.base64_encode('beem-key:beem-secret')));
    }

    public function test_the_messaging_tab_says_when_beem_has_no_balance_to_give(): void
    {
        Http::fake(['*vendors/balance*' => Http::response(['message' => 'Unauthorized'], 401)]);
        $this->put(route('admin.settings.messaging'), $this->messaging());

        $this->get(route('admin.settings.index', ['tab' => 'messaging']))->assertOk()
            ->assertSee(t('messaging.balanceUnavailable'));
    }

    public function test_creating_a_login_sends_sms_and_email_with_the_phone_as_username(): void
    {
        Http::fake(['*beem*' => Http::response(['successful' => true])]);
        Mail::fake();
        $this->put(route('admin.settings.messaging'), $this->messaging());

        $this->post(route('admin.members.login', $this->member))->assertSessionHas('login_results');

        $result = session('login_results')[0];
        $this->assertSame('created', $result['status']);
        $this->assertSame('0716123456', $result['username']);
        $this->assertTrue($result['sms']);
        $this->assertTrue($result['email']);

        $user = $this->member->fresh()->user;
        $this->assertTrue($user->hasRole('member'));
        $this->assertTrue($user->must_change_password);
        $this->assertTrue(Hash::check($result['password'], $user->password));
        Http::assertSent(fn ($request) => str_contains($request['message'], '0716123456') && str_contains($request['message'], $result['password'])
            && $request['recipients'][0]['dest_addr'] === '255716123456');
        Mail::assertSent(MemberLoginDetails::class, fn ($mail) => $mail->hasTo('asha@example.test') && $mail->temporaryPassword === $result['password']);
    }

    public function test_without_sms_or_email_set_up_the_admin_sees_the_password_to_hand_over(): void
    {
        Mail::fake();

        $this->post(route('admin.members.login', $this->member));

        $result = session('login_results')[0];
        $this->assertNull($result['sms']);
        $this->assertNull($result['email']);
        $this->assertNotEmpty($result['password']);
        Mail::assertNothingSent();
    }

    public function test_member_signs_in_with_any_phone_format_and_must_change_the_password_first(): void
    {
        $this->post(route('admin.members.login', $this->member));
        $temporary = session('login_results')[0]['password'];
        auth()->logout();

        $this->post(route('login'), ['login' => '+255716123456', 'password' => $temporary]);
        $this->assertAuthenticatedAs($this->member->fresh()->user);

        $this->get(route('member.home'))->assertRedirect(route('account.new-password'));
        $this->get(route('account.new-password'))->assertOk()->assertSee('name="current_password"', false);

        $this->put(route('account.password'), ['current_password' => $temporary, 'password' => 'asha-mpya-2026', 'password_confirmation' => 'asha-mpya-2026'])
            ->assertRedirect('/');
        $this->assertFalse($this->member->fresh()->user->must_change_password);
        $this->get(route('member.home'))->assertOk();
    }

    public function test_an_officer_who_is_also_a_member_gets_a_separate_member_login(): void
    {
        $this->admin->update(['email' => 'mkuu@example.test', 'password' => 'staff-password-1']);
        $officer = Member::factory()->create(['organization_id' => $this->organization->id, 'phone' => '0700000001', 'user_id' => $this->admin->id]);

        $this->post(route('admin.members.login', $officer));
        $result = session('login_results')[0];

        $this->assertSame('created', $result['status']);
        $this->assertSame('0700000001', $result['username']);
        $memberUser = $officer->fresh()->user;
        $this->assertNotSame($this->admin->id, $memberUser->id);
        $this->assertTrue($memberUser->hasRole('member'));
        $this->assertTrue(Hash::check('staff-password-1', $this->admin->fresh()->password));

        // phone + member password opens the member portal
        auth()->logout();
        $this->post(route('login'), ['login' => '0700000001', 'password' => $result['password']])->assertRedirect(route('member.home'));
        $this->assertAuthenticatedAs($memberUser);

        // phone or e-mail + staff password still opens the admin side
        foreach (['0700000001', 'mkuu@example.test'] as $login) {
            $this->post(route('logout'));
            $this->post(route('login'), ['login' => $login, 'password' => 'staff-password-1'])->assertRedirect(route('admin.dashboard'));
            $this->assertAuthenticatedAs($this->admin);
        }
    }

    public function test_the_mobile_app_signs_an_officer_into_their_member_account_with_the_member_password(): void
    {
        $officer = Member::factory()->create(['organization_id' => $this->organization->id, 'phone' => '0700000001']);
        $this->post(route('admin.members.login', $officer));
        $password = session('login_results')[0]['password'];

        $this->postJson('/api/v1/auth/login', ['login' => '+255700000001', 'password' => $password])
            ->assertOk()->assertJsonPath('data.user.id', $officer->fresh()->user_id);
    }

    public function test_bulk_send_covers_only_active_members_without_a_login(): void
    {
        Member::factory()->create(['organization_id' => $this->organization->id, 'phone' => '0716999888']);
        Member::factory()->create(['organization_id' => $this->organization->id, 'phone' => '0716555444', 'status' => 'suspended']);

        // an officer linked only to their staff account still needs a member login
        Member::factory()->create(['organization_id' => $this->organization->id, 'phone' => '0700000001', 'user_id' => $this->admin->id]);

        $this->post(route('admin.members.logins.missing'));
        $this->assertCount(3, session('login_results'));

        $this->post(route('admin.members.logins.missing'));
        $this->assertCount(0, session('login_results'));
    }

    /**
     * @return array<string, mixed>
     */
    private function messaging(): array
    {
        return [
            'sms_enabled' => '1', 'beem_api_key' => 'beem-key', 'beem_secret_key' => 'beem-secret', 'beem_sender_id' => 'UMOJA',
            'mail_enabled' => '1', 'mail_host' => 'smtp.hostinger.com', 'mail_port' => 465, 'mail_encryption' => 'ssl',
            'mail_username' => 'info@umojagroup.co.tz', 'mail_password' => 'mail-secret', 'mail_from_address' => 'info@umojagroup.co.tz', 'mail_from_name' => 'Umoja Group',
        ];
    }
}
