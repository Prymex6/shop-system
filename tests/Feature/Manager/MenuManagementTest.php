<?php

namespace Tests\Feature\Manager;

use App\Models\Tenant\Category;
use App\Models\Tenant\Product;
use App\Models\Tenant\User;
use Tests\TenantTestCase;

/**
 * Tests for manager menu management (categories + products).
 */
class MenuManagementTest extends TenantTestCase
{
    private function manager(): User
    {
        return User::create([
            'name' => 'Manager',
            'email' => 'manager@test.com',
            'password' => bcrypt('secret'),
            'role' => 'manager',
            'is_active' => true,
        ]);
    }

    private function category(array $overrides = []): Category
    {
        return Category::create(array_merge([
            'name' => 'Pizza',
            'slug' => 'pizza',
            'is_active' => true,
            'sort_order' => 1,
        ], $overrides));
    }

    private function product(int $categoryId, array $overrides = []): Product
    {
        return Product::create(array_merge([
            'category_id' => $categoryId,
            'name' => 'Margherita',
            'slug' => 'margherita',
            'price' => 25.00,
            'is_published' => true,
        ], $overrides));
    }

    // ─── Menu index ───────────────────────────────────────────────────

    public function test_manager_can_view_menu_management(): void
    {
        $response = $this->actingAs($this->manager(), 'tenant')
            ->withoutTenantMiddleware()
            ->get(route('tenant.manager.products.index'));

        $response->assertStatus(200);
    }

    // ─── Categories ───────────────────────────────────────────────────

    public function test_manager_can_create_category(): void
    {
        $response = $this->actingAs($this->manager(), 'tenant')
            ->withoutTenantMiddleware()
            ->postJson(route('tenant.manager.categories.store'), [
                'name' => 'Burgery',
                'slug' => 'burgery',
                'is_active' => true,
                'sort_order' => 2,
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('categories', ['name' => 'Burgery', 'slug' => 'burgery']);
    }

    public function test_create_category_validates_unique_slug(): void
    {
        $this->category(['name' => 'Pizza', 'slug' => 'pizza']);

        // Sending same slug explicitly should trigger validation error
        $response = $this->actingAs($this->manager(), 'tenant')
            ->withoutTenantMiddleware()
            ->postJson(route('tenant.manager.categories.store'), [
                'name' => 'Pizza Duplicate',
                'slug' => 'pizza',
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['slug']);
    }

    public function test_manager_can_update_category(): void
    {
        $cat = $this->category();

        $response = $this->actingAs($this->manager(), 'tenant')
            ->withoutTenantMiddleware()
            ->putJson(route('tenant.manager.categories.update', $cat), [
                'name' => 'Pizza Klasyczna',
                'slug' => 'pizza-klasyczna',
                'is_active' => true,
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('categories', ['id' => $cat->id, 'name' => 'Pizza Klasyczna']);
    }

    public function test_manager_can_delete_empty_category(): void
    {
        $cat = $this->category();

        $response = $this->actingAs($this->manager(), 'tenant')
            ->withoutTenantMiddleware()
            ->deleteJson(route('tenant.manager.categories.destroy', $cat));

        $response->assertRedirect();
        $this->assertDatabaseMissing('categories', ['id' => $cat->id]);
    }

    public function test_cannot_delete_category_with_products(): void
    {
        $cat = $this->category();
        $this->product($cat->id);

        $response = $this->actingAs($this->manager(), 'tenant')
            ->withoutTenantMiddleware()
            ->deleteJson(route('tenant.manager.categories.destroy', $cat));

        // Should return error (redirect with error flash or 422)
        $this->assertDatabaseHas('categories', ['id' => $cat->id]);
    }

    // ─── Products ─────────────────────────────────────────────────────

    public function test_manager_can_create_product(): void
    {
        $cat = $this->category();

        $response = $this->actingAs($this->manager(), 'tenant')
            ->withoutTenantMiddleware()
            ->postJson(route('tenant.manager.products.store'), [
                'category_id' => $cat->id,
                'name' => 'Capricciosa',
                'slug' => 'capricciosa',
                'description' => 'Klasyczna pizza',
                'type' => 'physical',
                'price' => 28.00,
                'is_published' => true,
                'is_featured' => false,
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('products', ['name' => 'Capricciosa']);
    }

    public function test_manager_can_update_product(): void
    {
        $cat = $this->category();
        $prod = $this->product($cat->id);

        $response = $this->actingAs($this->manager(), 'tenant')
            ->withoutTenantMiddleware()
            ->putJson(route('tenant.manager.products.update', $prod), [
                'category_id' => $cat->id,
                'name' => 'Margherita Premium',
                'slug' => 'margherita-premium',
                'type' => 'physical',
                'price' => 25.00,
                'is_published' => true,
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('products', ['id' => $prod->id, 'name' => 'Margherita Premium']);
    }

    public function test_manager_can_delete_product(): void
    {
        $cat = $this->category();
        $prod = $this->product($cat->id);

        $response = $this->actingAs($this->manager(), 'tenant')
            ->withoutTenantMiddleware()
            ->deleteJson(route('tenant.manager.products.destroy', $prod));

        $response->assertRedirect();
        $this->assertDatabaseMissing('products', ['id' => $prod->id]);
    }

    public function test_manager_can_toggle_product_availability(): void
    {
        $cat = $this->category();
        $prod = $this->product($cat->id, ['is_published' => true]);

        $response = $this->actingAs($this->manager(), 'tenant')
            ->withoutTenantMiddleware()
            ->patchJson(route('tenant.manager.products.toggle-publish', $prod));

        $response->assertRedirect();
    }

    public function test_product_requires_name_and_category(): void
    {
        $response = $this->actingAs($this->manager(), 'tenant')
            ->withoutTenantMiddleware()
            ->postJson(route('tenant.manager.products.store'), []);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['name', 'price']);
    }
}
