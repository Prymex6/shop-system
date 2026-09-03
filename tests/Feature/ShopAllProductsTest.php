<?php

namespace Tests\Feature;

use App\Models\Tenant\Category;
use App\Models\Tenant\Product;
use Tests\TenantTestCase;

/**
 * Round 23: "Cały sklep" / "Zobacz wszystkie" under Bestsellers/New
 * Arrivals/Featured Products on the homepage all linked to route('tenant.shop')
 * — the homepage itself, which only ever renders curated sections (max
 * 8-12 items each). Clicking any of them just reloaded the same page —
 * there was no actual "browse the full catalog" page anywhere in the app.
 */
class ShopAllProductsTest extends TenantTestCase
{
    private function product(array $overrides = []): Product
    {
        return Product::create(array_merge([
            'name' => 'Produkt', 'slug' => 'produkt-' . uniqid(),
            'sku' => 'ALL-' . uniqid(), 'price' => 10.0, 'is_active' => true,
            'is_published' => true, 'track_stock' => false,
        ], $overrides));
    }

    public function test_all_products_page_loads(): void
    {
        $response = $this->withoutTenantMiddleware()->get(route('tenant.shop.products'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Tenant/Client/Shop/Products')
            ->has('products')
            ->has('categories')
        );
    }

    public function test_lists_more_products_than_the_homepage_sections_would(): void
    {
        for ($i = 0; $i < 15; $i++) {
            $this->product();
        }

        $response = $this->withoutTenantMiddleware()->get(route('tenant.shop.products'));

        $response->assertInertia(fn ($page) => $page->where('products.total', 15));
    }

    public function test_excludes_unpublished_products(): void
    {
        $this->product(['name' => 'Widoczny']);
        $this->product(['name' => 'Ukryty', 'is_published' => false]);

        $response = $this->withoutTenantMiddleware()->get(route('tenant.shop.products'));

        $response->assertInertia(fn ($page) => $page->where('products.total', 1));
    }

    public function test_can_filter_by_category(): void
    {
        $catA = Category::create(['name' => 'A', 'slug' => 'cat-a', 'is_active' => true]);
        $catB = Category::create(['name' => 'B', 'slug' => 'cat-b', 'is_active' => true]);
        $this->product(['name' => 'In A', 'category_id' => $catA->id]);
        $this->product(['name' => 'In B', 'category_id' => $catB->id]);

        $response = $this->withoutTenantMiddleware()->get(route('tenant.shop.products', ['category' => 'cat-a']));

        $response->assertInertia(fn ($page) => $page->where('products.total', 1));
    }

    public function test_can_filter_by_price_range(): void
    {
        $this->product(['name' => 'Tani', 'price' => 5]);
        $this->product(['name' => 'Drogi', 'price' => 500]);

        $response = $this->withoutTenantMiddleware()->get(route('tenant.shop.products', ['max_price' => 50]));

        $response->assertInertia(fn ($page) => $page->where('products.total', 1));
    }
}
