<?php

namespace Tests\Feature;

use App\Models\Notification;
use App\Models\Organization;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdministrationTest extends TestCase
{
    use RefreshDatabase;

    private Organization $organization;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        config(['app.key' => 'base64:MTIzNDU2Nzg5MDEyMzQ1Njc4OTAxMjM0NTY3ODkwMTI=']);
        $this->organization = Organization::create(['name' => 'Umoja Test', 'code' => 'TEST']);
        foreach (['super_admin', 'admin', 'treasurer', 'accountant', 'loan_officer', 'member'] as $role) {
            Role::findOrCreate($role, 'web');
        }
        $adminRole = Role::findByName('admin');
        foreach (['users.view', 'users.manage', 'settings.view', 'settings.manage', 'roles.manage', 'notifications.send'] as $permission) {
            $adminRole->givePermissionTo(Permission::findOrCreate($permission, 'web'));
        }
        $this->admin = User::factory()->create(['organization_id' => $this->organization->id]);
        $this->admin->assignRole('admin');
        $this->actingAs($this->admin);
    }

    public function test_a_new_user_gets_the_password_the_admin_set_and_can_sign_in(): void
    {
        $this->post(route('admin.users.store'), [
            'name' => 'Asha Treasurer', 'email' => 'asha@example.test', 'role' => 'treasurer',
            'password' => 'kikoba-2026', 'password_confirmation' => 'kikoba-2026',
        ])->assertSessionHasNoErrors();

        $user = User::where('email', 'asha@example.test')->firstOrFail();
        $this->assertTrue(Hash::check('kikoba-2026', $user->password));
        $this->assertTrue($user->hasRole('treasurer'));
    }

    public function test_an_admin_cannot_create_a_super_admin(): void
    {
        $this->post(route('admin.users.store'), [
            'name' => 'Sneaky', 'email' => 'sneaky@example.test', 'role' => 'super_admin',
            'password' => 'kikoba-2026', 'password_confirmation' => 'kikoba-2026',
        ])->assertSessionHasErrors('role');
    }

    public function test_editing_a_member_account_keeps_them_a_member(): void
    {
        $member = User::factory()->create(['organization_id' => $this->organization->id]);
        $member->assignRole('member');

        $html = $this->get(route('admin.users.index'))->assertOk()->getContent();
        $this->assertMatchesRegularExpression('/<option value="member" selected/', $html);

        $this->put(route('admin.users.update', $member), ['name' => $member->name, 'status' => 'active', 'role' => 'member', 'password' => 'new-pass-123', 'password_confirmation' => 'new-pass-123'])
            ->assertSessionHasNoErrors();
        $this->assertTrue($member->fresh()->hasRole('member'));
        $this->assertTrue(Hash::check('new-pass-123', $member->fresh()->password));
    }

    public function test_users_change_their_own_password_with_the_current_one(): void
    {
        $this->admin->update(['password' => 'old-password']);

        $this->put(route('account.password'), ['current_password' => 'wrong', 'password' => 'brand-new-1', 'password_confirmation' => 'brand-new-1'])
            ->assertSessionHasErrors('current_password');
        $this->put(route('account.password'), ['current_password' => 'old-password', 'password' => 'brand-new-1', 'password_confirmation' => 'brand-new-1'])
            ->assertSessionHasNoErrors();

        $this->assertTrue(Hash::check('brand-new-1', $this->admin->fresh()->password));
        $this->get(route('admin.account'))->assertOk()->assertSee(t('account.changePassword'));
    }

    public function test_settings_only_store_known_keys(): void
    {
        $this->put(route('admin.settings.config'), ['share_price' => 5_000, 'is_admin' => '1'])->assertSessionHasNoErrors();

        $this->assertSame('5000', Setting::where('key', 'share_price')->value('value'));
        $this->assertNull(Setting::where('key', 'is_admin')->first());
    }

    public function test_admin_cannot_strip_role_management_from_their_own_role(): void
    {
        $this->put(route('admin.roles.update', 'admin'), ['permissions' => ['users.view']])->assertSessionHas('toast_tone', 'error');

        $this->assertTrue($this->admin->fresh()->can('roles.manage'));
    }

    public function test_announcements_are_in_app_only(): void
    {
        $this->post(route('admin.notifications.announce'), ['title' => 'Mkutano', 'message' => 'Jumamosi saa nne', 'channel' => 'sms'])->assertSessionHasNoErrors();

        $this->assertSame('in_app', Notification::firstOrFail()->channel);
    }

    public function test_forgot_password_page_does_not_pretend_to_send_a_link(): void
    {
        auth()->logout();

        $this->get(route('forgot'))->assertOk()->assertSee(t('auth.forgotStep2'))->assertDontSee('name="email"', false);
    }
}
