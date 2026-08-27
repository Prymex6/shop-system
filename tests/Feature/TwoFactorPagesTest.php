<?php

namespace Tests\Feature;

use App\Models\Tenant\User;
use Tests\TenantTestCase;

/**
 * resources/js/Pages/Tenant/Auth/TwoFactor/Enable.vue and Verify.vue did not
 * exist on disk before this fix — any staff member enabling 2FA, or logging
 * in with 2FA already enabled, hit a hard Inertia crash instead of a usable
 * page (Enable.vue was entirely unreachable, Verify.vue meant a total
 * account lockout since the middleware logs the user out before redirecting
 * there).
 */
class TwoFactorPagesTest extends TenantTestCase
{
    private function manager(): User
    {
        return User::create([
            'name' => 'Manager', 'email' => 'mgr@test.com',
            'password' => bcrypt('secret'), 'role' => 'manager', 'is_active' => true,
        ]);
    }

    public function test_manager_can_view_2fa_enable_page(): void
    {
        $response = $this->actingAs($this->manager(), 'tenant')
            ->withoutTenantMiddleware()
            ->get(route('tenant.manager.2fa.enable'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page->component('Tenant/Auth/TwoFactor/Enable')
            ->where('two_factor_enabled', false)
        );
    }

    public function test_verify_page_redirects_to_login_without_pending_2fa_session(): void
    {
        $response = $this->withoutTenantMiddleware()
            ->get(route('tenant.2fa.verify'));

        $response->assertRedirect(route('tenant.login'));
    }

    public function test_verify_page_renders_when_2fa_pending(): void
    {
        $manager = $this->manager();

        $response = $this->withoutTenantMiddleware()
            ->withSession(['2fa_user_id' => $manager->id])
            ->get(route('tenant.2fa.verify'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page->component('Tenant/Auth/TwoFactor/Verify'));
    }

    public function test_enabling_2fa_flashes_recovery_codes_shared_prop(): void
    {
        $manager = $this->manager();

        $response = $this->actingAs($manager, 'tenant')
            ->withoutTenantMiddleware()
            ->post(route('tenant.manager.2fa.enable.post'), ['password' => 'secret']);

        $response->assertRedirect();
        $response->assertSessionHas('recovery_codes');
        $this->assertTrue($manager->refresh()->two_factor_enabled);
    }
}
