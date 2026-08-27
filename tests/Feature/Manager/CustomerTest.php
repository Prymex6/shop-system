<?php

namespace Tests\Feature\Manager;

use App\Http\Middleware\Tenant\CheckSetupComplete;
use App\Http\Middleware\Tenant\CheckTenantLicense;
use App\Models\Tenant\Customer;
use App\Models\Tenant\Order;
use App\Models\Tenant\User;
use Stancl\Tenancy\Middleware\InitializeTenancyByDomain;
use Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains;
use Tests\TenantTestCase;

/**
 * Tests for manager customer management (S11).
 *
 * Covers:
 *   S11.1.1 – Customer list loads with pagination
 *   S11.1.2 – Search by name / email / phone
 *   S11.1.3 – Customer detail shows order history, total spent, favorite products
 *   S11.1.4 – CSV export downloads file
 */
class CustomerTest extends TenantTestCase
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

    private function makeCustomer(array $overrides = []): Customer
    {
        static $i = 0;
        $i++;

        return Customer::create(array_merge([
            'name' => "Klient $i",
            'email' => "klient$i@test.com",
            'password' => bcrypt('haslo'),
            'phone' => "60000000$i",
        ], $overrides));
    }

    // S11.1.1 – Customer list loads
    public function test_manager_can_view_customers_list(): void
    {
        $this->makeCustomer();
        $this->makeCustomer();

        $response = $this->actingAs($this->manager(), 'tenant')
            ->withoutTenantMiddleware()
            ->get(route('tenant.manager.customers.index'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('Tenant/Manager/Customers/Index')
            ->has('customers')
        );
    }

    // S11.1.2 – Search by name
    public function test_manager_can_search_customers_by_name(): void
    {
        $this->makeCustomer(['name' => 'Anna Nowak', 'email' => 'anna@test.com']);
        $this->makeCustomer(['name' => 'Piotr Kowalski', 'email' => 'piotr@test.com']);

        $response = $this->actingAs($this->manager(), 'tenant')
            ->withoutTenantMiddleware()
            ->get(route('tenant.manager.customers.index', ['search' => 'Anna']));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->where('filters.search', 'Anna')
        );
    }

    // S11.1.2 – Search by email
    public function test_manager_can_search_customers_by_email(): void
    {
        $this->makeCustomer(['name' => 'Test Email', 'email' => 'unique@email.com']);

        $response = $this->actingAs($this->manager(), 'tenant')
            ->withoutTenantMiddleware()
            ->get(route('tenant.manager.customers.index', ['search' => 'unique@email']));

        $response->assertStatus(200);
    }

    // S11.1.3 – Customer detail page loads with required props
    public function test_manager_can_view_customer_detail(): void
    {
        $customer = $this->makeCustomer();

        $response = $this->actingAs($this->manager(), 'tenant')
            ->withoutTenantMiddleware()
            ->get(route('tenant.manager.customers.show', $customer));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('Tenant/Manager/Customers/Show')
            ->has('customer')
            ->has('totalSpent')
            ->has('favoriteProducts')
        );
    }

    public function test_viewing_customer_detail_is_audit_logged(): void
    {
        $manager = $this->manager();
        $customer = $this->makeCustomer();

        $this->actingAs($manager, 'tenant')
            ->withoutTenantMiddleware()
            ->get(route('tenant.manager.customers.show', $customer));

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'customer.viewed',
            'subject_type' => 'Customer',
            'subject_id' => $customer->id,
            'user_id' => $manager->id,
        ]);
    }

    // S11.1.3 – totalSpent sums only paid/completed orders
    public function test_customer_total_spent_sums_paid_orders(): void
    {
        $customer = $this->makeCustomer();

        // Paid online order
        Order::create([
            'order_number' => 'ORD-001',
            'customer_id' => $customer->id,
            'customer_name' => $customer->name,
            'customer_email' => $customer->email,
            'customer_phone' => $customer->phone,
            'subtotal' => 100.00,
            'shipping_cost' => 0,
            'discount' => 0,
            'tax' => 0,
            'total' => 100.00,
            'status' => 'delivered',
            'fulfillment_status' => 'delivered',
            'payment_method' => 'przelewy24',
            'payment_status' => 'paid',
        ]);

        // Pending order – should NOT be counted
        Order::create([
            'order_number' => 'ORD-002',
            'customer_id' => $customer->id,
            'customer_name' => $customer->name,
            'customer_email' => $customer->email,
            'customer_phone' => $customer->phone,
            'subtotal' => 50.00,
            'shipping_cost' => 0,
            'discount' => 0,
            'tax' => 0,
            'total' => 50.00,
            'status' => 'pending',
            'fulfillment_status' => 'unfulfilled',
            'payment_method' => 'przelewy24',
            'payment_status' => 'awaiting_payment',
        ]);

        $response = $this->actingAs($this->manager(), 'tenant')
            ->withoutTenantMiddleware()
            ->get(route('tenant.manager.customers.show', $customer));

        $response->assertInertia(fn ($page) => $page->where('totalSpent', 100)
        );
    }

    // S11.1.4 – CSV export returns proper response
    public function test_manager_can_export_customers_csv(): void
    {
        $this->makeCustomer(['name' => 'Export Klient', 'email' => 'export@test.com']);

        $response = $this->actingAs($this->manager(), 'tenant')
            ->withoutTenantMiddleware()
            ->get(route('tenant.manager.customers.export'));

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
        $this->assertStringContainsString('export@test.com', $response->getContent());
    }

    // CSV export includes header row
    public function test_csv_export_includes_header(): void
    {
        $response = $this->actingAs($this->manager(), 'tenant')
            ->withoutTenantMiddleware()
            ->get(route('tenant.manager.customers.export'));

        $response->assertStatus(200);
        $this->assertStringContainsString('E-mail', $response->getContent());
        $this->assertStringContainsString('Punkty lojalnościowe', $response->getContent());
    }

    // Non-manager cannot access customer list
    public function test_chef_cannot_access_customers(): void
    {
        $chef = User::create([
            'name' => 'Chef', 'email' => 'chef@test.com',
            'password' => bcrypt('s'), 'role' => 'chef', 'is_active' => true,
        ]);

        $response = $this->actingAs($chef, 'tenant')
            ->withoutMiddleware([
                InitializeTenancyByDomain::class,
                PreventAccessFromCentralDomains::class,
                CheckSetupComplete::class,
                CheckTenantLicense::class,
            ])
            ->get(route('tenant.manager.customers.index'));

        $this->assertNotEquals(200, $response->getStatusCode());
    }
}
