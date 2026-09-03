<?php

namespace Tests\Feature;

use App\Models\Tenant\Product;
use App\Models\Tenant\User;
use Tests\TenantTestCase;

/**
 * Plan::max_products/max_staff columns didn't exist at all — a tenant on a
 * limited plan could add unbounded products or staff accounts regardless of
 * what they were paying for. ProductController::store()/StaffController::
 * store() now check tenancy()->tenant->plan the same way CheckoutController::
 * store() already does for max_orders_per_month. Faking tenancy()->tenant
 * mirrors PlanOrderLimitTest's established pattern.
 */
class PlanResourceLimitTest extends TenantTestCase
{
    private function fakeTenantWithLimits(?int $maxProducts, ?int $maxStaff): void
    {
        $plan = new \stdClass;
        $plan->max_products = $maxProducts;
        $plan->max_staff = $maxStaff;

        $fakeTenant = new class($plan)
        {
            public function __construct(public $plan) {}

            public function getAttribute($key)
            {
                return $key === 'id' ? 'test-tenant-id' : null;
            }
        };

        tenancy()->tenant = $fakeTenant;
    }

    private function manager(): User
    {
        return User::create([
            'name' => 'Manager', 'email' => 'manager@test.com',
            'password' => bcrypt('secret'), 'role' => 'manager', 'is_active' => true,
        ]);
    }

    // ─── Products ───────────────────────────────────────────────────────

    public function test_product_creation_is_blocked_once_plan_limit_is_reached(): void
    {
        $this->fakeTenantWithLimits(maxProducts: 1, maxStaff: null);
        Product::create(['name' => 'Istniejący', 'slug' => 'istniejacy', 'price' => 10]);

        $response = $this->actingAs($this->manager(), 'tenant')
            ->withoutTenantMiddleware()
            ->post(route('tenant.manager.products.store'), [
                'name' => 'Nowy', 'type' => 'physical', 'price' => 20,
            ]);

        $response->assertSessionHasErrors();
        $this->assertEquals(1, Product::count());
    }

    public function test_product_creation_is_allowed_when_under_the_limit(): void
    {
        $this->fakeTenantWithLimits(maxProducts: 5, maxStaff: null);

        $this->actingAs($this->manager(), 'tenant')
            ->withoutTenantMiddleware()
            ->post(route('tenant.manager.products.store'), [
                'name' => 'Nowy', 'type' => 'physical', 'price' => 20,
            ])->assertSessionHasNoErrors();

        $this->assertEquals(1, Product::count());
    }

    public function test_null_product_limit_means_unlimited(): void
    {
        $this->fakeTenantWithLimits(maxProducts: null, maxStaff: null);
        Product::create(['name' => 'A', 'slug' => 'a', 'price' => 10]);
        Product::create(['name' => 'B', 'slug' => 'b', 'price' => 10]);

        $this->actingAs($this->manager(), 'tenant')
            ->withoutTenantMiddleware()
            ->post(route('tenant.manager.products.store'), [
                'name' => 'C', 'type' => 'physical', 'price' => 20,
            ])->assertSessionHasNoErrors();

        $this->assertEquals(3, Product::count());
    }

    // ─── Staff ──────────────────────────────────────────────────────────

    public function test_staff_creation_is_blocked_once_plan_limit_is_reached(): void
    {
        $this->fakeTenantWithLimits(maxProducts: null, maxStaff: 1);
        $manager = $this->manager();

        $response = $this->actingAs($manager, 'tenant')
            ->withoutTenantMiddleware()
            ->post(route('tenant.manager.staff.store'), [
                'name' => 'Nowy Pracownik', 'email' => 'nowy@test.com',
                'password' => 'password123', 'role' => 'fulfillment',
            ]);

        $response->assertSessionHasErrors();
        $this->assertEquals(1, User::count());
    }

    public function test_staff_creation_is_allowed_when_under_the_limit(): void
    {
        $this->fakeTenantWithLimits(maxProducts: null, maxStaff: 5);

        $this->actingAs($this->manager(), 'tenant')
            ->withoutTenantMiddleware()
            ->post(route('tenant.manager.staff.store'), [
                'name' => 'Nowy Pracownik', 'email' => 'nowy@test.com',
                'password' => 'password123', 'role' => 'fulfillment',
            ])->assertSessionHasNoErrors();

        $this->assertEquals(2, User::count());
    }
}
