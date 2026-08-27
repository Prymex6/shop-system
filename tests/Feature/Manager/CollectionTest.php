<?php

namespace Tests\Feature\Manager;

use App\Models\Tenant\Collection;
use App\Models\Tenant\Product;
use App\Models\Tenant\User;
use Tests\TenantTestCase;

/**
 * Tests for product collection management.
 */
class CollectionTest extends TenantTestCase
{
    private function manager(): User
    {
        return User::create([
            'name' => 'Manager', 'email' => 'mgr@test.com',
            'password' => bcrypt('s'), 'role' => 'manager', 'is_active' => true,
        ]);
    }

    public function test_manager_can_view_collections(): void
    {
        $response = $this->actingAs($this->manager(), 'tenant')
            ->withoutTenantMiddleware()
            ->get(route('tenant.manager.collections.index'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('Tenant/Manager/Collections/Index')
            ->has('collections')
        );
    }

    public function test_manager_can_create_collection(): void
    {
        $response = $this->actingAs($this->manager(), 'tenant')
            ->withoutTenantMiddleware()
            ->postJson(route('tenant.manager.collections.store'), [
                'name' => 'Best Sellers',
                'slug' => 'best-sellers',
                'description' => 'Our most popular products.',
                'is_active' => true,
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('collections', ['name' => 'Best Sellers', 'slug' => 'best-sellers']);
    }

    public function test_collection_slug_must_be_unique(): void
    {
        Collection::create([
            'name' => 'Existing',
            'slug' => 'existing-slug',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->manager(), 'tenant')
            ->withoutTenantMiddleware()
            ->postJson(route('tenant.manager.collections.store'), [
                'name' => 'Duplicate',
                'slug' => 'existing-slug',
                'is_active' => true,
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['slug']);
    }

    public function test_manager_can_update_collection(): void
    {
        $collection = Collection::create([
            'name' => 'Old Name',
            'slug' => 'old-name',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->manager(), 'tenant')
            ->withoutTenantMiddleware()
            ->putJson(route('tenant.manager.collections.update', $collection), [
                'name' => 'New Name',
                'slug' => 'new-name',
                'description' => 'Updated description.',
                'is_active' => false,
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('collections', ['id' => $collection->id, 'name' => 'New Name', 'slug' => 'new-name']);
    }

    public function test_manager_can_delete_collection(): void
    {
        $collection = Collection::create([
            'name' => 'To Delete',
            'slug' => 'to-delete',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->manager(), 'tenant')
            ->withoutTenantMiddleware()
            ->deleteJson(route('tenant.manager.collections.destroy', $collection));

        $response->assertRedirect();
        $this->assertDatabaseMissing('collections', ['id' => $collection->id]);
    }

    public function test_manager_can_add_products_to_collection(): void
    {
        $collection = Collection::create([
            'name' => 'Featured',
            'slug' => 'featured',
            'is_active' => true,
        ]);

        $product = Product::create([
            'name' => 'P',
            'slug' => 'p',
            'price' => 9.99,
            'type' => 'physical',
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->manager(), 'tenant')
            ->withoutTenantMiddleware()
            ->postJson(route('tenant.manager.collections.products.add', $collection), [
                'product_id' => $product->id,
            ]);

        $response->assertRedirect();
        $this->assertTrue($collection->products()->where('products.id', $product->id)->exists());
    }
}
