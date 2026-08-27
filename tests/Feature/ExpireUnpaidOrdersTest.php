<?php

namespace Tests\Feature;

use App\Console\Commands\ExpireUnpaidOrders;
use App\Models\Tenant\Product;
use App\Models\Tenant\ProductVariant;
use App\Services\OrderCancellationService;
use Tests\TenantTestCase;

/**
 * Stock is decremented the moment checkout is submitted, for every payment
 * method — including online gateways, where the customer may simply never
 * return (abandoned payment session). Nothing ever released that stock
 * before. orders:expire-unpaid cancels old awaiting_payment orders,
 * reusing OrderCancellationService (variant-aware stock release).
 */
class ExpireUnpaidOrdersTest extends TenantTestCase
{
    private function orderWithVariantStock(array $overrides = []): array
    {
        $product = Product::create([
            'name' => 'P', 'slug' => 'p', 'price' => 10,
            'type' => 'physical', 'status' => 'active', 'track_stock' => true, 'stock_quantity' => 0,
        ]);
        $variant = ProductVariant::create([
            'product_id' => $product->id, 'sku' => 'P-M', 'attributes' => ['Size' => 'M'],
            'price' => 10, 'stock_quantity' => 2, 'is_active' => true,
        ]);
        $order = $this->createTestOrder(array_merge([
            'payment_method' => 'przelewy24',
            'payment_status' => 'awaiting_payment',
            'status' => 'pending',
        ], $overrides));
        $order->items()->create([
            'product_id' => $product->id, 'variant_id' => $variant->id,
            'name' => 'P (M)', 'quantity' => 1, 'price' => 10, 'total' => 10,
        ]);

        return [$order, $variant];
    }

    public function test_old_awaiting_payment_order_is_cancelled_and_stock_released(): void
    {
        [$order, $variant] = $this->orderWithVariantStock();
        $order->forceFill(['created_at' => now()->subHours(3)])->save();

        ExpireUnpaidOrders::expireForCurrentTenant(app(OrderCancellationService::class), 2);

        $this->assertEquals('cancelled', $order->fresh()->status);
        $this->assertEquals(3, $variant->fresh()->stock_quantity);
    }

    public function test_recent_awaiting_payment_order_is_not_touched(): void
    {
        [$order, $variant] = $this->orderWithVariantStock();
        $order->forceFill(['created_at' => now()->subMinutes(30)])->save();

        ExpireUnpaidOrders::expireForCurrentTenant(app(OrderCancellationService::class), 2);

        $this->assertEquals('pending', $order->fresh()->status);
        $this->assertEquals(2, $variant->fresh()->stock_quantity);
    }

    public function test_cash_on_delivery_pending_order_is_never_auto_cancelled(): void
    {
        [$order, $variant] = $this->orderWithVariantStock([
            'payment_method' => 'cash_on_delivery',
            'payment_status' => 'pending',
        ]);
        $order->forceFill(['created_at' => now()->subDays(5)])->save();

        ExpireUnpaidOrders::expireForCurrentTenant(app(OrderCancellationService::class), 2);

        $this->assertEquals('pending', $order->fresh()->status, 'COD orders are legitimately long-lived, not abandoned');
        $this->assertEquals(2, $variant->fresh()->stock_quantity);
    }

    public function test_already_paid_order_is_never_touched(): void
    {
        [$order, $variant] = $this->orderWithVariantStock(['payment_status' => 'paid', 'status' => 'paid']);
        $order->forceFill(['created_at' => now()->subHours(5)])->save();

        ExpireUnpaidOrders::expireForCurrentTenant(app(OrderCancellationService::class), 2);

        $this->assertEquals('paid', $order->fresh()->status);
        $this->assertEquals(2, $variant->fresh()->stock_quantity);
    }
}
