<?php

namespace Tests\Feature;

use App\Models\Tenant\Category;
use App\Models\Tenant\Product;
use App\Models\Tenant\ProductVariant;
use Tests\TenantTestCase;

/**
 * Tests for product stock tracking (inventory management).
 */
class InventoryTest extends TenantTestCase
{
    private function setupTrackedProduct(int $stock = 5): array
    {
        $cat = Category::create(['name' => 'Burger', 'slug' => 'burger', 'is_active' => true]);
        $product = Product::create([
            'category_id' => $cat->id,
            'name' => 'Big Burger',
            'slug' => 'big-burger',
            'price' => 20.00,
            'is_published' => true,
            'track_stock' => true,
            'stock_quantity' => $stock,
        ]);
        $variant = ProductVariant::create([
            'product_id' => $product->id,
            'attributes' => [],
            'price' => 20.00,
            'sort_order' => 1,
        ]);

        return [$product, $variant];
    }

    public function test_order_decrements_stock_quantity(): void
    {
        [$product, $variant] = $this->setupTrackedProduct(5);
        // Give the variant matching stock so checkout passes the stock check
        $variant->update(['stock_quantity' => 5]);
        $shippingMethod = $this->createTestShippingMethod();

        $this->withoutTenantMiddleware()
            ->postJson(route('tenant.checkout.store'), [
                'type' => 'pickup',
                'customer_name' => 'Test',
                'customer_email' => 'test@test.com',
                'customer_phone' => '123456789',
                'payment_method' => 'cash_on_delivery',
                'terms_accepted' => true,
                'subtotal' => 40.00,
                'shipping_method_id' => $shippingMethod->id,
                'shipping_address' => ['country' => 'PL'],
                'items' => [
                    ['product_id' => $product->id, 'variant_id' => $variant->id, 'quantity' => 2],
                ],
            ]);

        $variant->refresh();
        $this->assertEquals(3, $variant->stock_quantity);
    }

    public function test_order_blocked_when_out_of_stock(): void
    {
        [$product, $variant] = $this->setupTrackedProduct(0);

        $response = $this->withoutTenantMiddleware()
            ->postJson(route('tenant.checkout.store'), [
                'type' => 'pickup',
                'customer_name' => 'Test',
                'customer_email' => 'test@test.com',
                'customer_phone' => '123456789',
                'payment_method' => 'cash_on_delivery',
                'items' => [
                    ['product_id' => $product->id, 'variant_id' => $variant->id, 'quantity' => 1],
                ],
            ]);

        $response->assertStatus(422);
    }

    public function test_order_blocked_when_quantity_exceeds_stock(): void
    {
        [$product, $variant] = $this->setupTrackedProduct(3);

        $response = $this->withoutTenantMiddleware()
            ->postJson(route('tenant.checkout.store'), [
                'type' => 'pickup',
                'customer_name' => 'Test',
                'customer_email' => 'test@test.com',
                'customer_phone' => '123456789',
                'payment_method' => 'cash_on_delivery',
                'items' => [
                    ['product_id' => $product->id, 'variant_id' => $variant->id, 'quantity' => 5],
                ],
            ]);

        $response->assertStatus(422);
    }

    public function test_products_without_stock_tracking_are_not_restricted(): void
    {
        $cat = Category::create(['name' => 'C', 'slug' => 'c', 'is_active' => true]);
        $product = Product::create([
            'category_id' => $cat->id,
            'name' => 'Unlimited',
            'slug' => 'unlimited',
            'price' => 10.00,
            'is_published' => true,
            'track_stock' => false,
        ]);
        $variant = ProductVariant::create([
            'product_id' => $product->id, 'attributes' => [], 'price' => 10.00, 'sort_order' => 1,
        ]);
        $shippingMethod = $this->createTestShippingMethod();

        $response = $this->withoutTenantMiddleware()
            ->postJson(route('tenant.checkout.store'), [
                'type' => 'pickup',
                'customer_name' => 'Test',
                'customer_email' => 'test@test.com',
                'customer_phone' => '123456789',
                'payment_method' => 'cash_on_delivery',
                'terms_accepted' => true,
                'subtotal' => 10.00,
                'shipping_method_id' => $shippingMethod->id,
                'shipping_address' => ['country' => 'PL'],
                'items' => [
                    ['product_id' => $product->id, 'variant_id' => $variant->id, 'quantity' => 100],
                ],
            ]);

        $response->assertStatus(200);
    }
}
