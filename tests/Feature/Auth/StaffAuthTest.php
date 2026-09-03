<?php

namespace Tests\Feature\Auth;

use App\Http\Middleware\Tenant\CheckSetupComplete;
use App\Models\Tenant\User;
use Stancl\Tenancy\Middleware\InitializeTenancyByDomain;
use Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains;
use Tests\TenantTestCase;

/**
 * Tests for staff authentication (tenant guard).
 */
class StaffAuthTest extends TenantTestCase
{
    private function makeUser(string $role = 'manager', string $password = 'secret123'): User
    {
        return User::create([
            'name' => 'Test User',
            'email' => 'staff@test.com',
            'password' => bcrypt($password),
            'role' => $role,
            'is_active' => true,
        ]);
    }

    // ─── Login ────────────────────────────────────────────────────────

    public function test_login_page_loads(): void
    {
        $response = $this->withoutTenantMiddleware()->get(route('tenant.login'));
        $response->assertStatus(200);
    }

    public function test_valid_credentials_log_in_staff(): void
    {
        $this->makeUser('manager', 'secret123');

        $response = $this->withoutTenantMiddleware()
            ->postJson(route('tenant.login'), [
                'email' => 'staff@test.com',
                'password' => 'secret123',
            ]);

        $response->assertRedirect();
        $this->assertAuthenticatedAs(User::first(), 'tenant');
    }

    public function test_wrong_password_rejects_login(): void
    {
        $this->makeUser('manager', 'correct');

        $response = $this->withoutTenantMiddleware()
            ->postJson(route('tenant.login'), [
                'email' => 'staff@test.com',
                'password' => 'wrong',
            ]);

        $response->assertStatus(422);
        $this->assertGuest('tenant');
    }

    public function test_inactive_user_cannot_login(): void
    {
        User::create([
            'name' => 'Inactive',
            'email' => 'inactive@test.com',
            'password' => bcrypt('secret'),
            'role' => 'manager',
            'is_active' => false,
        ]);

        $response = $this->withoutTenantMiddleware()
            ->postJson(route('tenant.login'), [
                'email' => 'inactive@test.com',
                'password' => 'secret',
            ]);

        $response->assertStatus(422);
        $this->assertGuest('tenant');
    }

    // ─── Logout ───────────────────────────────────────────────────────

    public function test_logout_clears_session(): void
    {
        $user = $this->makeUser();

        $response = $this->actingAs($user, 'tenant')
            ->withoutTenantMiddleware()
            ->post(route('tenant.logout'));

        $response->assertRedirect();
        $this->assertGuest('tenant');
    }

    // ─── Role Redirect ────────────────────────────────────────────────

    public function test_manager_redirected_to_dashboard(): void
    {
        $user = $this->makeUser('manager');

        $response = $this->actingAs($user, 'tenant')
            ->withoutTenantMiddleware()
            ->get(route('tenant.staff.redirect'));

        $response->assertRedirect(route('tenant.manager.dashboard'));
    }

    public function test_fulfillment_staff_redirected_to_fulfillment_panel(): void
    {
        $user = $this->makeUser('chef'); // chef role redirects to fulfillment panel

        $response = $this->actingAs($user, 'tenant')
            ->withoutTenantMiddleware()
            ->get(route('tenant.staff.redirect'));

        $response->assertRedirect(route('tenant.staff.fulfillment'));
    }

    // ─── Protected routes require auth ────────────────────────────────

    public function test_dashboard_requires_auth(): void
    {
        $response = $this->withoutMiddleware([
            InitializeTenancyByDomain::class,
            PreventAccessFromCentralDomains::class,
            CheckSetupComplete::class,
        ])->get(route('tenant.manager.dashboard'));

        $response->assertStatus(302);
        $response->assertRedirect(route('tenant.login'));
    }

    public function test_login_required_field_validation(): void
    {
        $response = $this->withoutTenantMiddleware()
            ->postJson(route('tenant.login'), []);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['email', 'password']);
    }
}
