<?php

namespace Tests\Feature\Client;

use App\Models\Tenant\Order;
use App\Models\Tenant\OrderItem;
use App\Models\Tenant\Product;
use Illuminate\Support\Str;
use Tests\TenantTestCase;

/**
 * Round 23: the order confirmation page (the app's only real "thank you"
 * page) never offered anything beyond what was already bought.
 */
class OrderTrackingUpsellTest extends TenantTestCase
{
    private function product(string $name, float $price = 10.0): Product
    {
        return Product::create([
            'name' => $name, 'slug' => Str::slug($name) . '-' . uniqid(),
            'sku' => 'UP-' . uniqid(), 'price' => $price, 'is_active' => true,
            'is_published' => true, 'track_stock' => false,
        ]);
    }

    public function test_suggests_frequently_bought_together_products(): void
    {
        $bought = $this->product('Bought');
        $together = $this->product('Bought Together');

        $historical = Order::create([
            'order_number' => 'HIST-1', 'customer_email' => 'x@test.com', 'customer_name' => 'X',
            'status' => 'delivered', 'payment_status' => 'paid', 'payment_method' => 'bank_transfer',
            'subtotal' => 0, 'total' => 0,
        ]);
        OrderItem::create(['order_id' => $historical->id, 'product_id' => $bought->id, 'name' => $bought->name, 'quantity' => 1, 'price' => 10, 'total' => 10]);
        OrderItem::create(['order_id' => $historical->id, 'product_id' => $together->id, 'name' => $together->name, 'quantity' => 1, 'price' => 10, 'total' => 10]);

        $order = $this->createTestOrder(['order_number' => 'ORD-UP-1', 'status' => 'paid', 'tracking_token' => 'tok-1']);
        OrderItem::create(['order_id' => $order->id, 'product_id' => $bought->id, 'name' => $bought->name, 'quantity' => 1, 'price' => 10, 'total' => 10]);

        $response = $this->withoutTenantMiddleware()
            ->get(route('tenant.order.tracking', $order->order_number) . '?token=tok-1');

        $response->assertOk();
        $ids = collect($response->inertiaProps('upsellProducts'))->pluck('id');
        $this->assertTrue($ids->contains($together->id));
        $this->assertFalse($ids->contains($bought->id));
    }

    public function test_no_upsell_shown_for_cancelled_order(): void
    {
        $product = $this->product('Whatever');
        $order = $this->createTestOrder(['order_number' => 'ORD-UP-2', 'status' => 'cancelled', 'tracking_token' => 'tok-2']);
        OrderItem::create(['order_id' => $order->id, 'product_id' => $product->id, 'name' => $product->name, 'quantity' => 1, 'price' => 10, 'total' => 10]);

        $response = $this->withoutTenantMiddleware()
            ->get(route('tenant.order.tracking', $order->order_number) . '?token=tok-2');

        $response->assertOk();
        $this->assertEmpty($response->inertiaProps('upsellProducts'));
    }
}
