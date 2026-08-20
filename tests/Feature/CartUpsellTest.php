<?php

namespace Tests\Feature;

use App\Models\Tenant\Order;
use App\Models\Tenant\OrderItem;
use App\Models\Tenant\Product;
use Illuminate\Support\Str;
use Tests\TenantTestCase;

/**
 * Round 23: the cart sidebar never suggested anything beyond what was
 * already in it — cross-sell only ever existed on the product page
 * (ProductRecommendationService::getFrequentlyBoughtTogether()), never in
 * the cart itself, where the customer is closest to actually checking out.
 */
class CartUpsellTest extends TenantTestCase
{
    private function product(string $name, float $price = 10.0): Product
    {
        return Product::create([
            'name' => $name, 'slug' => Str::slug($name) . '-' . uniqid(),
            'sku' => 'U-' . uniqid(), 'price' => $price, 'is_active' => true,
            'is_published' => true, 'track_stock' => false,
        ]);
    }

    private function orderWithItems(array $products): Order
    {
        $order = Order::create([
            'order_number' => 'ORD-' . uniqid(), 'customer_email' => 'x@test.com',
            'customer_name' => 'X', 'status' => 'paid', 'payment_status' => 'paid',
            'payment_method' => 'bank_transfer', 'subtotal' => 0, 'total' => 0,
        ]);

        foreach ($products as $product) {
            OrderItem::create([
                'order_id' => $order->id, 'product_id' => $product->id,
                'name' => $product->name, 'quantity' => 1,
                'price' => $product->price, 'total' => $product->price,
            ]);
        }

        return $order;
    }

    public function test_returns_frequently_bought_together_products(): void
    {
        $a = $this->product('A');
        $b = $this->product('B');
        $this->orderWithItems([$a, $b]);

        $response = $this->withoutTenantMiddleware()
            ->getJson(route('tenant.cart.upsell', ['product_ids' => [$a->id]]));

        $response->assertOk();
        $ids = collect($response->json('products'))->pluck('id');
        $this->assertTrue($ids->contains($b->id));
    }

    public function test_excludes_products_already_in_cart(): void
    {
        $a = $this->product('A');
        $b = $this->product('B');
        $this->orderWithItems([$a, $b]);

        $response = $this->withoutTenantMiddleware()
            ->getJson(route('tenant.cart.upsell', ['product_ids' => [$a->id, $b->id]]));

        $response->assertOk();
        $ids = collect($response->json('products'))->pluck('id');
        $this->assertFalse($ids->contains($a->id));
        $this->assertFalse($ids->contains($b->id));
    }

    public function test_empty_product_ids_returns_empty_list(): void
    {
        $response = $this->withoutTenantMiddleware()->getJson(route('tenant.cart.upsell'));

        $response->assertOk();
        $this->assertEmpty($response->json('products'));
    }

    public function test_falls_back_to_bestsellers_when_no_purchase_history(): void
    {
        $a = $this->product('A');
        $bestseller = $this->product('Bestseller');
        $bestseller->update(['sales_count' => 999]);

        $response = $this->withoutTenantMiddleware()
            ->getJson(route('tenant.cart.upsell', ['product_ids' => [$a->id]]));

        $response->assertOk();
        $ids = collect($response->json('products'))->pluck('id');
        $this->assertTrue($ids->contains($bestseller->id));
    }
}
