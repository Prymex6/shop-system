<?php

namespace Tests\Feature\Manager;

use App\Models\Tenant\User;
use Illuminate\Support\Facades\Cache;
use Tests\TenantTestCase;

/**
 * Tests for landlord impersonation flow (S24).
 *
 * Covers:
 *   S24.1.1 – Valid token → manager logged in, impersonating flag set, redirect to dashboard
 *   S24.1.2 – Stop impersonation → logged out, redirect away
 *   S24.1.3 – Expired / missing token → 403
 */
class ImpersonateTest extends TenantTestCase
{
    private function manager(): User
    {
        return User::create([
            'name' => 'Manager',
            'email' => 'mgr@test.com',
            'password' => bcrypt('s'),
            'role' => 'manager',
            'is_active' => true,
        ]);
    }

    // S24.1.1 – Valid token logs manager in and redirects to dashboard
    public function test_valid_impersonation_token_logs_in_manager(): void
    {
        $manager = $this->manager();

        $token = 'test-impersonate-token-' . uniqid();
        Cache::store('file')->put('impersonate:' . $token, [
            'tenant_id' => tenant('id'),
            'user_id' => $manager->id,
        ], now()->addMinutes(5));

        $response = $this->withoutTenantMiddleware()
            ->get(route('tenant.manager.impersonate', ['token' => $token]));

        $response->assertRedirect(route('tenant.manager.dashboard'));
        $this->assertAuthenticatedAs($manager, 'tenant');
    }

    // S24.1.1 – Impersonating flag is stored in session
    public function test_impersonation_sets_session_flag(): void
    {
        $manager = $this->manager();

        $token = 'test-session-flag-' . uniqid();
        Cache::store('file')->put('impersonate:' . $token, [
            'tenant_id' => tenant('id'),
            'user_id' => $manager->id,
        ], now()->addMinutes(5));

        $response = $this->withoutTenantMiddleware()
            ->withSession([])
            ->get(route('tenant.manager.impersonate', ['token' => $token]));

        $response->assertSessionHas('impersonating', true);
    }

    // S24.1.2 – Stop impersonation logs out the manager
    public function test_stop_impersonation_logs_out_manager(): void
    {
        $manager = $this->manager();

        $response = $this->actingAs($manager, 'tenant')
            ->withoutTenantMiddleware()
            ->withSession(['impersonating' => true])
            ->post(route('tenant.manager.impersonate.stop'));

        $response->assertRedirect();
        $this->assertGuest('tenant');
    }

    // S24.1.2 – Stop impersonation clears session flag
    public function test_stop_impersonation_clears_session_flag(): void
    {
        $manager = $this->manager();

        $response = $this->actingAs($manager, 'tenant')
            ->withoutTenantMiddleware()
            ->withSession(['impersonating' => true])
            ->post(route('tenant.manager.impersonate.stop'));

        $response->assertSessionMissing('impersonating');
    }

    // S24.1.3 – Missing token returns 403
    public function test_missing_token_returns_403(): void
    {
        $response = $this->withoutTenantMiddleware()
            ->get(route('tenant.manager.impersonate'));

        $response->assertStatus(403);
    }

    // S24.1.3 – Expired / invalid token returns 403
    public function test_invalid_token_returns_403(): void
    {
        $response = $this->withoutTenantMiddleware()
            ->get(route('tenant.manager.impersonate', ['token' => 'totally-invalid-token']));

        $response->assertStatus(403);
    }

    // Regression: a token issued for a DIFFERENT tenant must never log
    // anyone in here, even though it's otherwise well-formed and unexpired —
    // tenant user IDs are not globally unique, so without this check a token
    // for tenant A's manager #1 could log the bearer in as tenant B's
    // manager #1 on a completely unrelated shop.
    public function test_token_for_different_tenant_is_rejected(): void
    {
        $manager = $this->manager();

        $token = 'cross-tenant-token-' . uniqid();
        Cache::store('file')->put('impersonate:' . $token, [
            'tenant_id' => 'some-other-tenant-id',
            'user_id' => $manager->id,
        ], now()->addMinutes(5));

        $response = $this->withoutTenantMiddleware()
            ->get(route('tenant.manager.impersonate', ['token' => $token]));

        $response->assertStatus(403);
        $this->assertGuest('tenant');
    }

    // Token is consumed (single-use) – second request returns 403
    public function test_token_is_single_use(): void
    {
        $manager = $this->manager();

        $token = 'single-use-token-' . uniqid();
        Cache::store('file')->put('impersonate:' . $token, [
            'tenant_id' => tenant('id'),
            'user_id' => $manager->id,
        ], now()->addMinutes(5));

        // First request succeeds
        $this->withoutTenantMiddleware()
            ->get(route('tenant.manager.impersonate', ['token' => $token]));

        // Second request with same token should fail (token was pulled/deleted)
        $response = $this->withoutTenantMiddleware()
            ->get(route('tenant.manager.impersonate', ['token' => $token]));

        $response->assertStatus(403);
    }
}
