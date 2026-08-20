<?php

namespace Tests\Feature;

use App\Models\Tenant\Order;
use App\Models\Tenant\Product;
use App\Models\Tenant\TaxRate;
use Tests\TenantTestCase;

/**
 * TaxController::storeOss() let a manager configure a per-country EU OSS
 * VAT rate, and the panel made it look like the shop already applied it —
 * but nothing in CheckoutController/TaxCalculatorService ever read the
 * customer's country or queried a stored OSS rate; every customer paid the
 * product's flat default rate regardless of destination. Fixed by resolving
 * the customer's country once in CheckoutController::store() and having
 * TaxCalculatorService prefer a matching active OSS rate when one exists.
 */
class EuVatCheckoutTest extends TenantTestCase
{
    private function product(float $price, ?TaxRate $taxRate = null): Product
    {
        return Product::create([
            'name' => 'Produkt', 'slug' => 'produkt-' . uniqid(),
            'sku' => 'P-' . uniqid(), 'price' => $price, 'is_active' => true,
            'is_published' => true, 'track_stock' => false,
            'tax_rate_id' => $taxRate?->id,
        ]);
    }

    private function checkoutPayload(Product $product, string $country, array $overrides = []): array
    {
        $shippingMethod = $this->createTestShippingMethod();

        return array_merge([
            'customer_name' => 'Jan Kowalski',
            'customer_email' => 'jan-' . uniqid() . '@example.com',
            'customer_phone' => '123456789',
            'payment_method' => 'cash_on_delivery',
            'terms_accepted' => true,
            'shipping_method_id' => $shippingMethod->id,
            'shipping_address' => ['country' => $country],
            'items' => [
                ['product_id' => $product->id, 'quantity' => 1],
            ],
        ], $overrides);
    }

    public function test_order_shipped_to_country_with_configured_oss_rate_uses_it(): void
    {
        $plRate = TaxRate::create(['name' => 'PL', 'rate' => 23, 'is_default' => true, 'is_active' => true]);
        TaxRate::create([
            'name' => 'Niemcy OSS', 'rate' => 19, 'country_code' => 'DE',
            'is_eu_oss' => true, 'eu_vat_rate' => 19, 'is_active' => true,
        ]);
        $product = $this->product(123.00, $plRate); // gross price, 23% would be baked in by default

        $this->withoutTenantMiddleware()
            ->postJson(route('tenant.checkout.store'), $this->checkoutPayload($product, 'DE'))
            ->assertOk();

        $order = Order::latest()->firstOrFail();
        $item = $order->items()->firstOrFail();

        $this->assertEquals(19.0, (float) $item->tax_rate);
    }

    public function test_order_shipped_domestically_still_uses_the_products_own_flat_rate(): void
    {
        $plRate = TaxRate::create(['name' => 'PL', 'rate' => 23, 'is_default' => true, 'is_active' => true]);
        TaxRate::create([
            'name' => 'Niemcy OSS', 'rate' => 19, 'country_code' => 'DE',
            'is_eu_oss' => true, 'eu_vat_rate' => 19, 'is_active' => true,
        ]);
        $product = $this->product(123.00, $plRate);

        $this->withoutTenantMiddleware()
            ->postJson(route('tenant.checkout.store'), $this->checkoutPayload($product, 'PL'))
            ->assertOk();

        $order = Order::latest()->firstOrFail();
        $item = $order->items()->firstOrFail();

        $this->assertEquals(23.0, (float) $item->tax_rate);
    }

    public function test_order_to_eu_country_without_configured_oss_rate_falls_back_to_flat_rate(): void
    {
        $plRate = TaxRate::create(['name' => 'PL', 'rate' => 23, 'is_default' => true, 'is_active' => true]);
        $product = $this->product(123.00, $plRate);
        // No OSS rate configured for France at all.

        $this->withoutTenantMiddleware()
            ->postJson(route('tenant.checkout.store'), $this->checkoutPayload($product, 'FR'))
            ->assertOk();

        $order = Order::latest()->firstOrFail();
        $item = $order->items()->firstOrFail();

        $this->assertEquals(23.0, (float) $item->tax_rate);
    }

    public function test_inactive_oss_rate_is_not_applied(): void
    {
        $plRate = TaxRate::create(['name' => 'PL', 'rate' => 23, 'is_default' => true, 'is_active' => true]);
        TaxRate::create([
            'name' => 'Niemcy OSS (wylaczona)', 'rate' => 19, 'country_code' => 'DE',
            'is_eu_oss' => true, 'eu_vat_rate' => 19, 'is_active' => false,
        ]);
        $product = $this->product(123.00, $plRate);

        $this->withoutTenantMiddleware()
            ->postJson(route('tenant.checkout.store'), $this->checkoutPayload($product, 'DE'))
            ->assertOk();

        $order = Order::latest()->firstOrFail();
        $item = $order->items()->firstOrFail();

        $this->assertEquals(23.0, (float) $item->tax_rate);
    }
}
