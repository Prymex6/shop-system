<?php

namespace Tests\Feature;

use App\Http\Middleware\CheckPermission;
use App\Http\Middleware\CheckRole;
use App\Http\Middleware\Tenant\CheckSetupComplete;
use App\Http\Middleware\Tenant\CheckTenantLicense;
use App\Models\Tenant\Customer;
use App\Models\Tenant\User;
use Stancl\Tenancy\Middleware\InitializeTenancyByDomain;
use Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains;
use Tests\TenantTestCase;

/**
 * Finding 1697: changing a password (via reset link or the self-service
 * "change password" form) never used to invalidate any other active session
 * for that account — a stolen or forgotten-open session on another device
 * stayed valid indefinitely, even past the point the real owner reset their
 * password specifically to lock an intruder out.
 *
 * Fixed in EnsureTenantAuth/EnsureCustomerAuth (Laravel's built-in
 * AuthenticateSession middleware can't be used here — it only ever checks
 * config('auth.defaults.guard'), never a named guard, and this app has no
 * 'web'-guard login at all): each request compares a password hash stored
 * in the session against the account's current one, logging the session out
 * the moment they no longer match.
 */
class SessionInvalidationTest extends TenantTestCase
{
    private function staffUser(string $password = 'secret123'): User
    {
        return User::create([
            'name' => 'Staff', 'email' => 'staff@test.com',
            'password' => bcrypt($password), 'role' => 'manager', 'is_active' => true,
        ]);
    }

    private function customer(string $password = 'secret123'): Customer
    {
        return Customer::create([
            'name' => 'Klient', 'email' => 'klient@test.com',
            'password' => bcrypt($password), 'phone' => '123456789',
        ]);
    }

    // ─── tenant (staff/manager) guard ──────────────────────────────────

    /**
     * Excludes everything tenantMiddlewareToExclude() would (tenancy
     * bootstrap, setup/license/role/permission checks) except EnsureTenantAuth
     * itself, which is exactly the middleware under test here.
     */
    private function withoutMiddlewareExceptTenantAuth(): static
    {
        return $this->withoutMiddleware([
            InitializeTenancyByDomain::class,
            PreventAccessFromCentralDomains::class,
            CheckSetupComplete::class,
            CheckTenantLicense::class,
            CheckRole::class,
            CheckPermission::class,
        ]);
    }

    public function test_tenant_session_with_stale_password_hash_is_logged_out(): void
    {
        $user = $this->staffUser();

        $response = $this->actingAs($user, 'tenant')
            ->withoutMiddlewareExceptTenantAuth()
            ->withSession(['password_hash_tenant' => 'not-the-real-hash'])
            ->get(route('tenant.manager.dashboard'));

        $response->assertRedirect(route('tenant.login'));
        $this->assertGuest('tenant');
    }

    public function test_tenant_session_with_matching_password_hash_stays_logged_in(): void
    {
        $user = $this->staffUser();

        $response = $this->actingAs($user, 'tenant')
            ->withoutMiddlewareExceptTenantAuth()
            ->withSession(['password_hash_tenant' => $user->password])
            ->get(route('tenant.manager.dashboard'));

        $response->assertStatus(200);
        $this->assertAuthenticatedAs($user, 'tenant');
    }

    public function test_tenant_first_request_lazily_stores_hash_without_logout(): void
    {
        $user = $this->staffUser();

        // No password_hash_tenant in the session yet — this is the state
        // every already-logged-in session is in the moment this fix ships;
        // it must not force-logout everyone on deploy.
        $response = $this->actingAs($user, 'tenant')
            ->withoutMiddlewareExceptTenantAuth()
            ->get(route('tenant.manager.dashboard'));

        $response->assertStatus(200);
        $this->assertEquals($user->password, session('password_hash_tenant'));
    }

    // ─── customer guard ─────────────────────────────────────────────────

    public function test_customer_session_with_stale_password_hash_is_logged_out(): void
    {
        $customer = $this->customer();

        $response = $this->actingAs($customer, 'customer')
            ->withoutTenantMiddleware()
            ->withSession(['password_hash_customer' => 'not-the-real-hash'])
            ->get(route('tenant.account'));

        $response->assertRedirect(route('tenant.client.login'));
        $this->assertGuest('customer');
    }

    public function test_customer_session_with_matching_password_hash_stays_logged_in(): void
    {
        $customer = $this->customer();

        $response = $this->actingAs($customer, 'customer')
            ->withoutTenantMiddleware()
            ->withSession(['password_hash_customer' => $customer->password])
            ->get(route('tenant.account'));

        $response->assertStatus(200);
        $this->assertAuthenticatedAs($customer, 'customer');
    }

    public function test_customer_own_session_survives_its_own_password_change(): void
    {
        $customer = $this->customer('oldPassword123');

        // First protected request establishes the session's stored hash —
        // mirrors an already-logged-in browsing session before it changes
        // its own password.
        $this->actingAs($customer, 'customer')
            ->withoutTenantMiddleware()
            ->get(route('tenant.account'))
            ->assertStatus(200);

        $this->putJson(route('tenant.account.password'), [
            'current_password' => 'oldPassword123',
            'password' => 'newPassword456',
            'password_confirmation' => 'newPassword456',
        ])->assertRedirect();

        // The very session that performed the change must stay logged in —
        // only OTHER sessions should be invalidated by a password change.
        $this->get(route('tenant.account'))->assertStatus(200);
        $this->assertAuthenticatedAs($customer, 'customer');
    }

    public function test_other_customer_session_is_logged_out_after_password_reset(): void
    {
        $customer = $this->customer('oldPassword123');

        // Simulates a second, already-logged-in browser session that
        // established its stored hash before the password was reset
        // elsewhere (e.g. via the "forgot password" email flow).
        $this->actingAs($customer, 'customer')
            ->withoutTenantMiddleware()
            ->get(route('tenant.account'))
            ->assertStatus(200);
        $this->assertEquals($customer->password, session('password_hash_customer'));

        $customer->forceFill(['password' => bcrypt('resetOnAnotherDevice789')])->save();

        $this->get(route('tenant.account'))->assertRedirect(route('tenant.client.login'));
        $this->assertGuest('customer');
    }
}
