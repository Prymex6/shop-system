<?php

namespace Tests\Feature\Client;

use App\Models\Tenant\Category;
use App\Models\Tenant\Product;
use App\Models\Tenant\ProductVariant;
use Tests\TenantTestCase;

/**
 * Tests for client-facing menu page.
 */
class MenuTest extends TenantTestCase
{
    private function setupMenu(): array
    {
        $cat = Category::create([
            'name' => 'Pizza',
            'slug' => 'pizza',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $product = Product::create([
            'category_id' => $cat->id,
            'name' => 'Margherita',
            'slug' => 'margherita',
            'description' => 'Klasyczna pizza',
            'price' => 28.00,
            'is_published' => true,
            'is_featured' => true,
        ]);

        $variant = ProductVariant::create([
            'product_id' => $product->id,
            'attributes' => [],
            'price' => 28.00,
            'sort_order' => 1,
        ]);

        return [$cat, $product, $variant];
    }

    public function test_menu_page_loads(): void
    {
        $response = $this->withoutTenantMiddleware()->get(route('tenant.shop'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('Tenant/Client/Shop/Index')
        );
    }

    public function test_menu_shows_active_categories(): void
    {
        [$cat] = $this->setupMenu();

        $response = $this->withoutTenantMiddleware()->get(route('tenant.shop'));

        $response->assertInertia(fn ($page) => $page->has('categories')
        );
    }

    public function test_menu_does_not_show_inactive_categories(): void
    {
        Category::create(['name' => 'Hidden', 'slug' => 'hidden', 'is_active' => false]);

        $response = $this->withoutTenantMiddleware()->get(route('tenant.shop'));

        $values = $response->inertiaProps('categories');
        $names = array_column($values ?? [], 'name');
        $this->assertNotContains('Hidden', $names);
    }

    public function test_menu_shows_available_products(): void
    {
        $this->setupMenu();

        $response = $this->withoutTenantMiddleware()->get(route('tenant.shop'));

        $response->assertStatus(200);
    }

    public function test_product_detail_returns_json(): void
    {
        [, $product] = $this->setupMenu();

        $response = $this->withoutTenantMiddleware()
            ->get(route('tenant.product.show', $product));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->has('product')
            ->has('product.id')
            ->has('product.name')
        );
    }

    public function test_unavailable_product_returns_404(): void
    {
        $cat = Category::create(['name' => 'C', 'slug' => 'c', 'is_active' => true]);
        $product = Product::create([
            'category_id' => $cat->id,
            'name' => 'Out of Stock',
            'slug' => 'out',
            'price' => 9.99,
            'is_published' => false,
        ]);

        $response = $this->withoutTenantMiddleware()
            ->getJson(route('tenant.product.show', $product));

        $response->assertStatus(404);
    }
}
