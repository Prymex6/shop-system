<?php

namespace Tests\Feature;

use App\Models\Tenant\Order;
use App\Models\Tenant\Product;
use App\Models\Tenant\ProductBundle;
use Illuminate\Support\Str;
use Tests\TenantTestCase;

/**
 * ProductBundle was fully configurable in the manager panel but had no
 * storefront presence at all — no page to view one, and CheckoutController::
 * store() only ever understood product_id cart items, never bundle_id.
 * BundleController::show() is the new client-facing page; CheckoutController
 * now expands a bundle cart entry into one order_item per real component,
 * priced proportionally so the components sum to the bundle's own price.
 */
class BundlePurchaseTest extends TenantTestCase
{
    private function product(string $name, float $price, int $stock = 50): Product
    {
        return Product::create([
            'name' => $name, 'slug' => Str::slug($name) . '-' . uniqid(),
            'sku' => 'P-' . uniqid(), 'price' => $price, 'is_active' => true,
            'is_published' => true, 'track_stock' => true, 'stock_quantity' => $stock,
        ]);
    }

    private function checkoutPayload(array $items, array $overrides = []): array
    {
        $shippingMethod = $this->createTestShippingMethod();

        return array_merge([
            'customer_name' => 'Jan Kowalski',
            'customer_email' => 'jan-' . uniqid() . '@example.com',
            'customer_phone' => '123456789',
            'payment_method' => 'cash_on_delivery',
            'terms_accepted' => true,
            'shipping_method_id' => $shippingMethod->id,
            'shipping_address' => ['country' => 'PL'],
            'items' => $items,
        ], $overrides);
    }

    public function test_bundle_show_page_renders_active_bundle(): void
    {
        $product = $this->product('Koszulka', 40);
        $bundle = ProductBundle::create(['name' => 'Zestaw', 'slug' => 'zestaw-' . uniqid(), 'price' => 30, 'is_active' => true]);
        $bundle->items()->create(['product_id' => $product->id, 'quantity' => 1]);

        $response = $this->withoutTenantMiddleware()->get(route('tenant.bundle.show', $bundle));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('Tenant/Client/Shop/Bundle')
            ->where('bundle.id', $bundle->id)
            ->has('bundle.items', 1)
        );
    }

    public function test_inactive_bundle_returns_404(): void
    {
        $bundle = ProductBundle::create(['name' => 'Ukryty', 'slug' => 'ukryty-' . uniqid(), 'price' => 30, 'is_active' => false]);

        $response = $this->withoutTenantMiddleware()->get(route('tenant.bundle.show', $bundle));

        $response->assertStatus(404);
    }

    public function test_bundle_can_be_purchased_and_decomposes_into_component_order_items(): void
    {
        $shirt = $this->product('Koszulka', 40); // weight 40*1=40
        $mug = $this->product('Kubek', 20);     // weight 20*1=20
        $bundle = ProductBundle::create(['name' => 'Zestaw', 'slug' => 'zestaw-' . uniqid(), 'price' => 45, 'is_active' => true]);
        $bundle->items()->create(['product_id' => $shirt->id, 'quantity' => 1]);
        $bundle->items()->create(['product_id' => $mug->id, 'quantity' => 1]);

        $response = $this->withoutTenantMiddleware()->postJson(
            route('tenant.checkout.store'),
            $this->checkoutPayload([['bundle_id' => $bundle->id, 'quantity' => 1]])
        );

        $response->assertOk();
        $order = Order::latest()->firstOrFail();
        $order->load('items');

        $this->assertCount(2, $order->items);
        $this->assertTrue($order->items->every(fn ($i) => $i->bundle_id === $bundle->id && $i->bundle_name === 'Zestaw'));

        // Components sum to the bundle's own price (45), not the sum of
        // their individual list prices (40 + 20 = 60).
        $this->assertEqualsWithDelta(45.0, (float) $order->items->sum('total'), 0.01);

        // Proportional split by list-price weight: shirt is 2/3 of the
        // combined weight (40 of 60), mug is 1/3 (20 of 60).
        $shirtItem = $order->items->firstWhere('product_id', $shirt->id);
        $mugItem = $order->items->firstWhere('product_id', $mug->id);
        $this->assertEqualsWithDelta(30.0, (float) $shirtItem->total, 0.01);
        $this->assertEqualsWithDelta(15.0, (float) $mugItem->total, 0.01);

        // Stock actually decremented for both components — this is exactly
        // the step that silently no-op'd when the decrement loop iterated
        // the raw (un-expanded) request payload instead of the expanded items.
        $this->assertEquals(49, $shirt->fresh()->stock_quantity);
        $this->assertEquals(49, $mug->fresh()->stock_quantity);
    }

    public function test_bundle_quantity_multiplies_component_quantities_and_stock_decrement(): void
    {
        $product = $this->product('Naklejka', 5, stock: 100);
        $bundle = ProductBundle::create(['name' => 'Paczka naklejek', 'slug' => 'naklejki-' . uniqid(), 'price' => 12, 'is_active' => true]);
        $bundle->items()->create(['product_id' => $product->id, 'quantity' => 3]); // 3 per bundle

        $this->withoutTenantMiddleware()->postJson(
            route('tenant.checkout.store'),
            $this->checkoutPayload([['bundle_id' => $bundle->id, 'quantity' => 2]]) // 2 bundles
        )->assertOk();

        $order = Order::latest()->firstOrFail();
        $item = $order->items()->firstOrFail();

        $this->assertEquals(6, $item->quantity); // 3 per bundle × 2 bundles
        $this->assertEqualsWithDelta(24.0, (float) $item->total, 0.01); // 12 × 2 bundles
        $this->assertEquals(94, $product->fresh()->stock_quantity); // 100 - 6
    }

    public function test_bundle_purchase_fails_when_a_component_is_out_of_stock(): void
    {
        $product = $this->product('Ograniczony', 10, stock: 1);
        $bundle = ProductBundle::create(['name' => 'Zestaw', 'slug' => 'zestaw-' . uniqid(), 'price' => 10, 'is_active' => true]);
        $bundle->items()->create(['product_id' => $product->id, 'quantity' => 5]);

        $response = $this->withoutTenantMiddleware()->postJson(
            route('tenant.checkout.store'),
            $this->checkoutPayload([['bundle_id' => $bundle->id, 'quantity' => 1]])
        );

        $response->assertStatus(422);
        $this->assertEquals(0, Order::count());
        $this->assertEquals(1, $product->fresh()->stock_quantity);
    }

    public function test_mixed_cart_of_plain_product_and_bundle_checks_out_correctly(): void
    {
        $standalone = $this->product('Osobny produkt', 15);
        $bundleProduct = $this->product('W zestawie', 20);
        $bundle = ProductBundle::create(['name' => 'Zestaw', 'slug' => 'zestaw-' . uniqid(), 'price' => 20, 'is_active' => true]);
        $bundle->items()->create(['product_id' => $bundleProduct->id, 'quantity' => 1]);

        $response = $this->withoutTenantMiddleware()->postJson(
            route('tenant.checkout.store'),
            $this->checkoutPayload([
                ['product_id' => $standalone->id, 'quantity' => 1],
                ['bundle_id' => $bundle->id, 'quantity' => 1],
            ])
        );

        $response->assertOk();
        $order = Order::latest()->firstOrFail();
        $this->assertCount(2, $order->items);
        $this->assertNull($order->items->firstWhere('product_id', $standalone->id)->bundle_id);
        $this->assertEquals($bundle->id, $order->items->firstWhere('product_id', $bundleProduct->id)->bundle_id);
    }

    public function test_inactive_bundle_cannot_be_checked_out(): void
    {
        $product = $this->product('Produkt', 10);
        $bundle = ProductBundle::create(['name' => 'Wylaczony', 'slug' => 'wylaczony-' . uniqid(), 'price' => 10, 'is_active' => false]);
        $bundle->items()->create(['product_id' => $product->id, 'quantity' => 1]);

        $response = $this->withoutTenantMiddleware()->postJson(
            route('tenant.checkout.store'),
            $this->checkoutPayload([['bundle_id' => $bundle->id, 'quantity' => 1]])
        );

        $response->assertStatus(422);
        $this->assertEquals(0, Order::count());
    }
}
