<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Auth\SessionGuard;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class LoginSecurityTest extends TestCase
{
    use RefreshDatabase;

    private Organization $organization;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        Cache::flush();
        config(['app.key' => 'base64:MTIzNDU2Nzg5MDEyMzQ1Njc4OTAxMjM0NTY3ODkwMTI=']);
        $this->organization = Organization::create(['name' => 'Umoja Group', 'code' => 'UMOJA']);
    }

    public function test_login_has_no_sample_credentials_and_has_security_headers(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertSee('Umoja Group')
            ->assertSee('name="_token"', false)
            ->assertDontSee('demo1234')
            ->assertDontSee('admin@kikoba.co.tz')
            ->assertDontSee('Demo accounts')
            ->assertHeader('X-Frame-Options', 'DENY')
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('Content-Security-Policy', "frame-ancestors 'none'; base-uri 'self'; form-action 'self'")
            ->assertHeader('Cache-Control', 'max-age=0, no-store, private');
    }

    public function test_member_can_sign_in_without_otp_even_when_sms_is_enabled(): void
    {
        config(['services.beem.enabled' => true]);
        $user = $this->user();
        $user->assignRole(Role::findOrCreate('member', 'web'));

        $this->post(route('login'), ['login' => $user->email, 'password' => 'password'])
            ->assertRedirect(route('member.home'))
            ->assertSessionMissing('otp_user')
            ->assertCookieMissing('remember_web_'.sha1(SessionGuard::class));

        $this->assertAuthenticatedAs($user);
        $this->assertNotNull($user->fresh()->last_login_at);
        $this->assertDatabaseCount('otp_codes', 0);
    }

    public function test_phone_login_and_explicit_remember_me_work(): void
    {
        $user = $this->user();
        $this->post(route('login'), ['login' => $user->phone, 'password' => 'password', 'remember' => '1'])
            ->assertRedirect(route('admin.dashboard'))
            ->assertCookie(auth()->guard('web')->getRecallerName());
        $this->assertAuthenticatedAs($user);
    }

    public function test_inactive_and_unknown_accounts_use_the_same_generic_error(): void
    {
        $user = $this->user(['status' => 'suspended']);
        foreach ([$user->email, 'unknown@example.test'] as $login) {
            $this->post(route('login'), ['login' => $login, 'password' => 'password'])
                ->assertSessionHasErrors(['login' => t('auth.badCredentials')]);
            $this->assertGuest();
        }
    }

    public function test_five_failed_attempts_block_even_a_correct_password_until_cooldown(): void
    {
        $user = $this->user();
        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->post(route('login'), ['login' => $user->email, 'password' => 'wrong-password'])
                ->assertSessionHasErrors(['login' => t('auth.badCredentials')]);
        }

        $this->post(route('login'), ['login' => $user->email, 'password' => 'password'])
            ->assertSessionHasErrors('login');
        $this->assertGuest();
        $this->travel(61)->seconds();

        $this->post(route('login'), ['login' => $user->email, 'password' => 'password'])
            ->assertRedirect(route('admin.dashboard'));
        $this->assertAuthenticatedAs($user);
    }

    public function test_ip_limit_covers_attempts_with_different_usernames(): void
    {
        for ($attempt = 0; $attempt < 30; $attempt++) {
            $this->post(route('login'), ['login' => "unknown{$attempt}@example.test", 'password' => 'wrong-password'])
                ->assertStatus(302);
        }
        $this->post(route('login'), ['login' => 'another@example.test', 'password' => 'wrong-password'])
            ->assertTooManyRequests();
        $this->assertGuest();
    }

    public function test_failed_login_does_not_flash_password(): void
    {
        $this->post(route('login'), ['login' => 'unknown@example.test', 'password' => 'private-password'])
            ->assertSessionHasErrors('login')
            ->assertSessionMissing('_old_input.password');
    }

    public function test_legacy_otp_route_cannot_authenticate_a_user(): void
    {
        $this->withSession(['otp_user' => 'stale-user', 'otp_destination' => '255700000000'])
            ->post(route('verify-otp'), ['code' => '123456'])
            ->assertRedirect(route('login'));
        $this->assertGuest();
        $this->get(route('login'))->assertSessionMissing('otp_user');
    }

    public function test_oversized_credentials_are_rejected(): void
    {
        $this->post(route('login'), ['login' => str_repeat('a', 256), 'password' => str_repeat('a', 1025)])
            ->assertSessionHasErrors(['login', 'password']);
        $this->assertGuest();
    }

    /** @param array<string, mixed> $attributes */
    private function user(array $attributes = []): User
    {
        return User::factory()->create([
            'organization_id' => $this->organization->id,
            'phone' => '255700123456',
            ...$attributes,
        ]);
    }
}
