<?php

namespace Tests\Feature;

use App\Models\Tenant\Product;
use App\Models\Tenant\Setting;
use Illuminate\Support\Str;
use Tests\TenantTestCase;

/**
 * Round 23: checkout had no order bump at all — no way to offer a
 * merchant-picked addon in the one moment a customer is most likely to say
 * yes to a small extra (right before paying).
 */
class OrderBumpTest extends TenantTestCase
{
    private function product(string $name, float $price = 9.99): Product
    {
        return Product::create([
            'name' => $name, 'slug' => Str::slug($name) . '-' . uniqid(),
            'sku' => 'BUMP-' . uniqid(), 'price' => $price, 'is_active' => true,
            'is_published' => true, 'track_stock' => false,
        ]);
    }

    public function test_checkout_page_has_no_order_bump_when_unconfigured(): void
    {
        $response = $this->withoutTenantMiddleware()->get(route('tenant.checkout'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page->where('orderBumpProduct', null));
    }

    public function test_checkout_page_offers_the_configured_order_bump_product(): void
    {
        $product = $this->product('Gratis Sample', 4.99);
        Setting::set('order_bump_product_id', $product->id, 'integer');

        $response = $this->withoutTenantMiddleware()->get(route('tenant.checkout'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->where('orderBumpProduct.id', $product->id)
            ->where('orderBumpProduct.name', 'Gratis Sample')
            ->where('orderBumpProduct.price', 4.99)
        );
    }

    public function test_order_bump_is_hidden_if_configured_product_was_unpublished(): void
    {
        $product = $this->product('Unpublished Bump');
        $product->update(['is_published' => false]);
        Setting::set('order_bump_product_id', $product->id, 'integer');

        $response = $this->withoutTenantMiddleware()->get(route('tenant.checkout'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page->where('orderBumpProduct', null));
    }
}
