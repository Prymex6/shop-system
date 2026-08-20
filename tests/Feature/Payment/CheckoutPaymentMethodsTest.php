<?php

namespace Tests\Feature\Payment;

use Illuminate\Testing\TestResponse;
use Tests\TenantTestCase;

/**
 * Tests that the Checkout page correctly passes available payment methods
 * based on tenant settings.
 *
 * NOTE: We do NOT send X-Inertia header here because assertInertia() uses
 * assertViewHas('page') which requires an HTML view response (not JSON).
 * For data extraction we use ->inertiaProps() instead of ->json().
 */
class CheckoutPaymentMethodsTest extends TenantTestCase
{
    /** GET /checkout as a regular (non-Inertia) request so assertInertia() works. */
    private function getCheckout(): TestResponse
    {
        return $this->withoutTenantMiddleware()
            ->get(route('tenant.checkout'));
    }

    /** Extract paymentMethods values array from response. */
    private function paymentValues(TestResponse $response): array
    {
        $data = $response->inertiaProps('paymentMethods');

        return array_column($data ?? [], 'value');
    }

    public function test_checkout_page_loads_with_payment_methods(): void
    {
        $this->setSettings([
            'payment_cash_on_delivery_enabled' => '1',
            'payment_bank_transfer_enabled' => '0',
        ]);

        $response = $this->getCheckout();

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('Tenant/Client/Checkout')
            ->has('paymentMethods')
        );
    }

    public function test_checkout_includes_cash_when_enabled(): void
    {
        $this->setSettings(['payment_cash_on_delivery_enabled' => '1']);

        $values = $this->paymentValues($this->getCheckout());

        $this->assertContains('cash_on_delivery', $values);
    }

    public function test_checkout_excludes_cash_when_disabled(): void
    {
        $this->setSettings(['payment_cash_on_delivery_enabled' => '0']);

        $response = $this->getCheckout();

        $response->assertInertia(fn ($page) => $page->count('paymentMethods', 0)
        );
    }

    public function test_checkout_includes_card_on_delivery_when_enabled(): void
    {
        $this->setSettings([
            'payment_cash_on_delivery_enabled' => '0',
            'payment_bank_transfer_enabled' => '1',
        ]);

        $values = $this->paymentValues($this->getCheckout());

        $this->assertContains('bank_transfer', $values);
        $this->assertNotContains('cash_on_delivery', $values);
    }

    public function test_checkout_includes_p24_when_configured_and_enabled(): void
    {
        $this->setSettings([
            'payment_cash_on_delivery_enabled' => '0',
            'payment_p24_enabled' => '1',
            'p24_merchant_id' => '123456',
            'p24_api_key' => 'test-key',
            'p24_crc' => 'test-crc',
        ]);

        $values = $this->paymentValues($this->getCheckout());

        $this->assertContains('przelewy24', $values);
    }

    public function test_checkout_excludes_gateway_when_not_configured(): void
    {
        $this->setSettings([
            'payment_p24_enabled' => '1',
            // No p24_merchant_id, p24_api_key, p24_crc → not configured
        ]);

        $values = $this->paymentValues($this->getCheckout());

        $this->assertNotContains('przelewy24', $values);
    }

    public function test_checkout_includes_multiple_online_gateways(): void
    {
        $this->setSettings([
            'payment_cash_on_delivery_enabled' => '1',
            'payment_p24_enabled' => '1',
            'p24_merchant_id' => '123456',
            'p24_api_key' => 'test-key',
            'p24_crc' => 'test-crc',
        ]);

        $values = $this->paymentValues($this->getCheckout());

        $this->assertContains('cash_on_delivery', $values);
        $this->assertContains('przelewy24', $values);
        $this->assertCount(2, $values);
    }

    public function test_online_payment_methods_have_type_online(): void
    {
        $this->setSettings([
            'payment_p24_enabled' => '1',
            'p24_merchant_id' => '123456',
            'p24_api_key' => 'key',
            'p24_crc' => 'crc',
        ]);

        $data = $this->getCheckout()->inertiaProps('paymentMethods');
        $p24 = collect($data)->firstWhere('value', 'przelewy24');

        $this->assertNotNull($p24);
        $this->assertEquals('online', $p24['type']);
        $this->assertArrayHasKey('color', $p24);
        $this->assertArrayHasKey('description', $p24);
    }
}
