<?php

namespace Tests\Feature\Manager;

use App\Models\Tenant\Product;
use App\Models\Tenant\ProductBundle;
use App\Models\Tenant\User;
use Tests\TenantTestCase;

/**
 * Tests for product bundle management.
 */
class ProductBundleTest extends TenantTestCase
{
    private function manager(): User
    {
        return User::create([
            'name' => 'Manager', 'email' => 'mgr@test.com',
            'password' => bcrypt('s'), 'role' => 'manager', 'is_active' => true,
        ]);
    }

    private function createProduct(string $name = 'Product', string $slug = 'product', float $price = 9.99): Product
    {
        return Product::create([
            'name' => $name,
            'slug' => $slug,
            'price' => $price,
            'type' => 'physical',
            'status' => 'active',
        ]);
    }

    public function test_manager_can_view_bundles(): void
    {
        $response = $this->actingAs($this->manager(), 'tenant')
            ->withoutTenantMiddleware()
            ->get(route('tenant.manager.bundles.index'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('Tenant/Manager/ProductBundles/Index', false)
            ->has('bundles')
        );
    }

    public function test_manager_can_create_bundle(): void
    {
        $product = $this->createProduct();

        $response = $this->actingAs($this->manager(), 'tenant')
            ->withoutTenantMiddleware()
            ->postJson(route('tenant.manager.bundles.store'), [
                'name' => 'Starter Bundle',
                'price' => 19.99,
                'items' => [
                    ['product_id' => $product->id, 'quantity' => 2],
                ],
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('product_bundles', ['name' => 'Starter Bundle', 'price' => 19.99]);
    }

    public function test_manager_can_update_bundle(): void
    {
        $bundle = ProductBundle::create([
            'name' => 'Old Bundle',
            'slug' => 'old-bundle',
            'price' => 15.00,
        ]);

        $product = $this->createProduct();

        $response = $this->actingAs($this->manager(), 'tenant')
            ->withoutTenantMiddleware()
            ->putJson(route('tenant.manager.bundles.update', $bundle), [
                'name' => 'Updated Bundle',
                'price' => 24.99,
                'items' => [
                    ['product_id' => $product->id, 'quantity' => 1],
                ],
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('product_bundles', ['id' => $bundle->id, 'name' => 'Updated Bundle', 'price' => 24.99]);
    }

    public function test_manager_can_delete_bundle(): void
    {
        $bundle = ProductBundle::create([
            'name' => 'To Delete',
            'slug' => 'to-delete',
            'price' => 9.99,
        ]);

        $response = $this->actingAs($this->manager(), 'tenant')
            ->withoutTenantMiddleware()
            ->deleteJson(route('tenant.manager.bundles.destroy', $bundle));

        $response->assertRedirect();
        $this->assertDatabaseMissing('product_bundles', ['id' => $bundle->id]);
    }

    public function test_bundle_requires_name_and_price(): void
    {
        $response = $this->actingAs($this->manager(), 'tenant')
            ->withoutTenantMiddleware()
            ->postJson(route('tenant.manager.bundles.store'), []);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['name', 'price']);
    }
}
