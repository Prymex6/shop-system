<?php

namespace Tests\Feature\Manager;

use App\Models\Tenant\Product;
use App\Models\Tenant\User;
use App\Models\Tenant\VolumeDiscount;
use Tests\TenantTestCase;

/**
 * Tests for volume discount CRUD management.
 *
 * Covers:
 *   – Manager can view the volume discounts list
 *   – Manager can create, update and delete a volume discount
 *   – min_quantity is validated to be at least 1
 */
class VolumeDiscountTest extends TenantTestCase
{
    private function manager(): User
    {
        return User::create([
            'name' => 'Manager', 'email' => 'mgr@test.com',
            'password' => bcrypt('s'), 'role' => 'manager', 'is_active' => true,
        ]);
    }

    private function makeProduct(): Product
    {
        return Product::create([
            'name' => 'Test Product',
            'slug' => 'test-product-' . rand(),
            'price' => 49.99,
        ]);
    }

    public function test_manager_can_view_volume_discounts(): void
    {
        $response = $this->actingAs($this->manager(), 'tenant')
            ->withoutTenantMiddleware()
            ->get(route('tenant.manager.volume-discounts.index'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('Tenant/Manager/VolumeDiscounts/Index')
            ->has('volumeDiscounts')
        );
    }

    public function test_manager_can_create_volume_discount(): void
    {
        $product = $this->makeProduct();

        $response = $this->actingAs($this->manager(), 'tenant')
            ->withoutTenantMiddleware()
            ->postJson(route('tenant.manager.volume-discounts.store'), [
                'product_id' => $product->id,
                'min_quantity' => 5,
                'discount_type' => 'percentage',
                'discount_value' => 10.00,
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('volume_discounts', [
            'product_id' => $product->id,
            'min_quantity' => 5,
            'discount_type' => 'percentage',
            'discount_value' => 10.00,
        ]);
    }

    public function test_manager_can_update_volume_discount(): void
    {
        $product = $this->makeProduct();
        $discount = VolumeDiscount::create([
            'product_id' => $product->id,
            'min_quantity' => 3,
            'discount_type' => 'percentage',
            'discount_value' => 5.00,
        ]);

        $response = $this->actingAs($this->manager(), 'tenant')
            ->withoutTenantMiddleware()
            ->putJson(route('tenant.manager.volume-discounts.update', $discount), [
                'product_id' => $product->id,
                'min_quantity' => 10,
                'discount_type' => 'fixed',
                'discount_value' => 20.00,
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('volume_discounts', [
            'id' => $discount->id,
            'min_quantity' => 10,
            'discount_type' => 'fixed',
            'discount_value' => 20.00,
        ]);
    }

    public function test_manager_can_delete_volume_discount(): void
    {
        $product = $this->makeProduct();
        $discount = VolumeDiscount::create([
            'product_id' => $product->id,
            'min_quantity' => 2,
            'discount_type' => 'percentage',
            'discount_value' => 5.00,
        ]);

        $response = $this->actingAs($this->manager(), 'tenant')
            ->withoutTenantMiddleware()
            ->deleteJson(route('tenant.manager.volume-discounts.destroy', $discount));

        $response->assertRedirect();
        $this->assertDatabaseMissing('volume_discounts', ['id' => $discount->id]);
    }

    public function test_min_quantity_must_be_at_least_1(): void
    {
        $product = $this->makeProduct();

        $response = $this->actingAs($this->manager(), 'tenant')
            ->withoutTenantMiddleware()
            ->postJson(route('tenant.manager.volume-discounts.store'), [
                'product_id' => $product->id,
                'min_quantity' => 0,
                'discount_type' => 'percentage',
                'discount_value' => 10.00,
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['min_quantity']);
    }
}
