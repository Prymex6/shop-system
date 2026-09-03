<?php

namespace Tests\Feature\Staff;

use App\Models\Tenant\Category;
use App\Models\Tenant\Product;
use App\Models\Tenant\ProductVariant;
use App\Models\Tenant\User;
use Tests\TenantTestCase;

/**
 * Tests for fulfillment staff panel functionality.
 */
class PosTest extends TenantTestCase
{
    private function fulfillmentStaff(): User
    {
        return User::create([
            'name' => 'Fulfillment', 'email' => 'fulfillment@test.com',
            'password' => bcrypt('s'), 'role' => 'fulfillment', 'is_active' => true,
        ]);
    }

    private function setupProduct(): array
    {
        $cat = Category::create(['name' => 'Odzież', 'slug' => 'odziez', 'is_active' => true]);
        $product = Product::create([
            'category_id' => $cat->id,
            'name' => 'Koszulka testowa',
            'slug' => 'koszulka-testowa',
            'price' => 25.00,
            'is_published' => true,
        ]);
        $variant = ProductVariant::create([
            'product_id' => $product->id,
            'attributes' => [],
            'price' => 25.00,
            'sort_order' => 1,
        ]);

        return [$product, $variant];
    }

    public function test_fulfillment_panel_loads_for_staff(): void
    {
        $response = $this->actingAs($this->fulfillmentStaff(), 'tenant')
            ->withoutTenantMiddleware()
            ->get(route('tenant.staff.fulfillment'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->has('orders')
        );
    }

    public function test_fulfillment_panel_shows_available_orders(): void
    {
        $this->createTestOrder([
            'status' => 'confirmed',
            'payment_status' => 'paid',
            'fulfillment_status' => 'unfulfilled',
        ]);

        $response = $this->actingAs($this->fulfillmentStaff(), 'tenant')
            ->withoutTenantMiddleware()
            ->get(route('tenant.staff.fulfillment'));

        $response->assertStatus(200);
    }

    public function test_fulfillment_staff_can_update_order_fulfillment_status(): void
    {
        $this->setupProduct();

        $order = $this->createTestOrder([
            'status' => 'confirmed',
            'payment_status' => 'paid',
            'fulfillment_status' => 'unfulfilled',
        ]);

        $response = $this->actingAs($this->fulfillmentStaff(), 'tenant')
            ->withoutTenantMiddleware()
            ->patchJson(route('tenant.staff.fulfillment.update-status', $order), [
                'fulfillment_status' => 'processing',
            ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'fulfillment_status' => 'processing',
        ]);
    }

    public function test_status_update_requires_fulfillment_status_field(): void
    {
        $order = $this->createTestOrder(['status' => 'confirmed', 'fulfillment_status' => 'unfulfilled']);

        $response = $this->actingAs($this->fulfillmentStaff(), 'tenant')
            ->withoutTenantMiddleware()
            ->patchJson(route('tenant.staff.fulfillment.update-status', $order), []);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['fulfillment_status']);
    }

    public function test_manager_can_also_use_fulfillment_panel(): void
    {
        $manager = User::create([
            'name' => 'Manager', 'email' => 'mgr@test.com',
            'password' => bcrypt('s'), 'role' => 'manager', 'is_active' => true,
        ]);

        $response = $this->actingAs($manager, 'tenant')
            ->withoutTenantMiddleware()
            ->get(route('tenant.staff.fulfillment'));

        $response->assertStatus(200);
    }
}
