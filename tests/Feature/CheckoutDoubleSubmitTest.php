<?php

namespace Tests\Feature;

use App\Models\Tenant\Order;
use App\Models\Tenant\Product;
use App\Models\Tenant\ShippingMethod;
use App\Models\Tenant\ShippingZone;
use Tests\TenantTestCase;

/**
 * Checkout.vue's double-submit protection was purely client-side (a local
 * isSubmitting flag) — two open tabs, the browser back button after
 * redirecting to a payment gateway, or a plain network retry could all fire
 * CheckoutController::store() twice for the same cart. Product::
 * lockForUpdate() only prevents overselling, not duplicate orders: if stock
 * covers both requests, both succeed and create two fully valid orders.
 * A short-lived Cache::add() lock keyed by a fingerprint of the cart now
 * rejects an identical resubmission within the window instead.
 */
class CheckoutDoubleSubmitTest extends TenantTestCase
{
    private function product(): Product
    {
        return Product::create([
            'name' => 'Produkt', 'slug' => 'produkt-' . uniqid(),
            'sku' => 'P-' . uniqid(), 'price' => 50, 'is_active' => true,
            'is_published' => true, 'track_stock' => false,
        ]);
    }

    private function payload(Product $product, array $overrides = []): array
    {
        $shippingMethod = $this->createTestShippingMethod();

        return array_merge([
            'customer_name' => 'Jan Kowalski',
            'customer_email' => 'jan@example.com',
            'customer_phone' => '123456789',
            'payment_method' => 'cash_on_delivery',
            'terms_accepted' => true,
            'shipping_method_id' => $shippingMethod->id,
            'shipping_address' => ['country' => 'PL'],
            'items' => [
                ['product_id' => $product->id, 'quantity' => 1],
            ],
        ], $overrides);
    }

    public function test_identical_resubmission_is_rejected_and_only_one_order_is_created(): void
    {
        $product = $this->product();
        $payload = $this->payload($product);

        $first = $this->withoutTenantMiddleware()->postJson(route('tenant.checkout.store'), $payload);
        $first->assertOk();

        $second = $this->withoutTenantMiddleware()->postJson(route('tenant.checkout.store'), $payload);
        $second->assertStatus(429);

        $this->assertEquals(1, Order::where('customer_email', 'jan@example.com')->count());
    }

    public function test_different_cart_is_not_blocked_by_an_unrelated_lock(): void
    {
        $productA = $this->product();
        $productB = $this->product();

        $this->withoutTenantMiddleware()
            ->postJson(route('tenant.checkout.store'), $this->payload($productA))
            ->assertOk();

        $this->withoutTenantMiddleware()
            ->postJson(route('tenant.checkout.store'), $this->payload($productB))
            ->assertOk();

        $this->assertEquals(2, Order::where('customer_email', 'jan@example.com')->count());
    }

    public function test_lock_is_released_after_a_legitimate_failure_so_retry_succeeds(): void
    {
        $product = $this->product();

        // Same shipping_method_id (part of the checkout fingerprint) used for
        // both attempts — only its zone attachment changes between them, which
        // isn't part of the fingerprint, so a naive "always keep the lock"
        // implementation would incorrectly block the second, otherwise-valid
        // attempt too.
        $method = ShippingMethod::create(['name' => 'Kurier', 'type' => 'standard', 'price' => 10, 'is_active' => true]);
        $payload = $this->payload($product, ['shipping_method_id' => $method->id]);

        // Not attached to any zone yet — ShippingCalculatorService::calculateCost()
        // can't find a pivot and returns null, which store() turns into a 422.
        $first = $this->withoutTenantMiddleware()->postJson(route('tenant.checkout.store'), $payload);
        $first->assertStatus(422);

        $zone = ShippingZone::firstOrCreate(['is_default' => true], ['name' => 'Default Zone', 'countries' => ['PL']]);
        $zone->methods()->attach($method->id);

        $second = $this->withoutTenantMiddleware()->postJson(route('tenant.checkout.store'), $payload);
        $second->assertOk();
    }
}
