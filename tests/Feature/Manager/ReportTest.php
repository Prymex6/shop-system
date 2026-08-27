<?php

namespace Tests\Feature\Manager;

use App\Models\Tenant\Customer;
use App\Models\Tenant\User;
use Tests\TenantTestCase;

/**
 * Tests for manager reports (stats, CSV export).
 */
class ReportTest extends TenantTestCase
{
    private function manager(): User
    {
        return User::create([
            'name' => 'Manager', 'email' => 'mgr@test.com',
            'password' => bcrypt('s'), 'role' => 'manager', 'is_active' => true,
        ]);
    }

    public function test_reports_page_loads(): void
    {
        $response = $this->actingAs($this->manager(), 'tenant')
            ->withoutTenantMiddleware()
            ->get(route('tenant.manager.reports.index'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('Tenant/Manager/Reports/Index')
            ->has('summary')
        );
    }

    public function test_reports_page_includes_revenue_data(): void
    {
        $this->createTestOrder(['total' => 99.99, 'status' => 'delivered', 'payment_status' => 'paid']);

        $response = $this->actingAs($this->manager(), 'tenant')
            ->withoutTenantMiddleware()
            ->get(route('tenant.manager.reports.index'));

        $response->assertInertia(fn ($page) => $page->has('summary')
            ->has('revenueChart')
            ->has('topProducts')
        );
    }

    public function test_reports_can_filter_by_date_range(): void
    {
        $response = $this->actingAs($this->manager(), 'tenant')
            ->withoutTenantMiddleware()
            ->get(route('tenant.manager.reports.index') . '?from=2026-01-01&to=2026-12-31');

        $response->assertStatus(200);
    }

    public function test_csv_export_returns_csv_file(): void
    {
        $this->createTestOrder(['status' => 'delivered']);

        $response = $this->actingAs($this->manager(), 'tenant')
            ->withoutTenantMiddleware()
            ->get(route('tenant.manager.reports.export-csv'));

        $response->assertStatus(200);
        $this->assertStringContainsString('text/csv', $response->headers->get('Content-Type'));
    }

    public function test_csv_export_with_date_range(): void
    {
        $response = $this->actingAs($this->manager(), 'tenant')
            ->withoutTenantMiddleware()
            ->get(route('tenant.manager.reports.export-csv') . '?from=2026-01-01&to=2026-12-31');

        $response->assertStatus(200);
    }

    public function test_retention_analysis_counts_customer_second_order(): void
    {
        $customer = Customer::create([
            'name' => 'Repeat Buyer', 'email' => 'repeat@test.com', 'password' => bcrypt('s'),
        ]);

        $firstOrder = $this->createTestOrder([
            'customer_id' => $customer->id,
            'payment_status' => 'paid',
            'created_at' => now()->subDays(20),
        ]);
        $firstOrder->forceFill(['created_at' => now()->subDays(20)])->save();

        $secondOrder = $this->createTestOrder([
            'customer_id' => $customer->id,
            'payment_status' => 'paid',
            'created_at' => now()->subDays(10),
        ]);
        $secondOrder->forceFill(['created_at' => now()->subDays(10)])->save();

        $response = $this->actingAs($this->manager(), 'tenant')
            ->withoutTenantMiddleware()
            ->getJson(route('tenant.manager.reports.retention'));

        $response->assertStatus(200);
        $data = $response->json();
        $this->assertNotEmpty($data);
        // 10-day gap between the two orders falls inside every bucket (30/60/90)
        $this->assertEquals(100.0, $data[0]['returned_30']);
    }
}
