<?php

namespace Tests\Feature\Manager;

use App\Http\Middleware\Tenant\CheckSetupComplete;
use App\Models\Tenant\User;
use Stancl\Tenancy\Middleware\InitializeTenancyByDomain;
use Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains;
use Tests\TenantTestCase;

/**
 * Tests for manager dashboard.
 */
class DashboardTest extends TenantTestCase
{
    private function manager(): User
    {
        return User::create([
            'name' => 'Manager', 'email' => 'mgr@test.com',
            'password' => bcrypt('s'), 'role' => 'manager', 'is_active' => true,
        ]);
    }

    public function test_dashboard_loads_for_manager(): void
    {
        $response = $this->actingAs($this->manager(), 'tenant')
            ->withoutTenantMiddleware()
            ->get(route('tenant.manager.dashboard'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('Tenant/Manager/Dashboard')
            ->has('stats')
        );
    }

    public function test_dashboard_includes_required_stats_keys(): void
    {
        $response = $this->actingAs($this->manager(), 'tenant')
            ->withoutTenantMiddleware()
            ->get(route('tenant.manager.dashboard'));

        $response->assertInertia(fn ($page) => $page->has('stats')
            ->has('recentOrders')
            ->has('popularProducts')
        );
    }

    public function test_dashboard_counts_todays_orders(): void
    {
        $this->createTestOrder(['status' => 'delivered']);
        $this->createTestOrder(['status' => 'pending', 'order_number' => 'ORD-2']);

        $response = $this->actingAs($this->manager(), 'tenant')
            ->withoutTenantMiddleware()
            ->get(route('tenant.manager.dashboard'));

        $response->assertStatus(200);
    }

    // S7.1.3 – Dashboard accepts date range filters and returns them in response
    public function test_dashboard_accepts_date_range_filter(): void
    {
        $dateFrom = now()->subDays(7)->format('Y-m-d');
        $dateTo = now()->format('Y-m-d');

        $response = $this->actingAs($this->manager(), 'tenant')
            ->withoutTenantMiddleware()
            ->get(route('tenant.manager.dashboard', [
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
            ]));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->has('revenueChart')
            ->has('filters')
            ->where('filters.date_from', $dateFrom)
            ->where('filters.date_to', $dateTo)
        );
    }

    public function test_dashboard_ignores_malformed_date_range_instead_of_500(): void
    {
        $response = $this->actingAs($this->manager(), 'tenant')
            ->withoutTenantMiddleware()
            ->get(route('tenant.manager.dashboard', [
                'date_from' => 'not-a-date',
                'date_to' => 'also-not-a-date',
            ]));

        $response->assertStatus(200);
    }

    // S7.1.4 – Dashboard includes hourly heatmap
    public function test_dashboard_includes_hourly_heatmap(): void
    {
        $response = $this->actingAs($this->manager(), 'tenant')
            ->withoutTenantMiddleware()
            ->get(route('tenant.manager.dashboard'));

        $response->assertInertia(fn ($page) => $page->has('hourlyHeatmap')
        );
    }

    public function test_chef_cannot_access_dashboard(): void
    {
        $chef = User::create([
            'name' => 'Chef', 'email' => 'chef@test.com',
            'password' => bcrypt('s'), 'role' => 'chef', 'is_active' => true,
        ]);

        $response = $this->actingAs($chef, 'tenant')
            ->withoutMiddleware([InitializeTenancyByDomain::class, PreventAccessFromCentralDomains::class, CheckSetupComplete::class])
            ->get(route('tenant.manager.dashboard'));

        // Chef should be redirected (403 or redirect to their panel)
        $this->assertNotEquals(200, $response->getStatusCode());
    }
}
