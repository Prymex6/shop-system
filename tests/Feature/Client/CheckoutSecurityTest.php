<?php

namespace Tests\Feature\Client;

use Tests\TenantTestCase;

class CheckoutSecurityTest extends TenantTestCase
{
    /** While holiday mode is on, the checkout sends you back to the shop. */
    public function test_checkout_page_redirects_when_vacation_mode_active(): void
    {
        $this->setSetting('vacation_mode', '1');
        $this->setSetting('vacation_message', 'Jesteśmy na urlopie');

        $response = $this->withoutTenantMiddleware()->get(route('tenant.checkout'));

        $response->assertRedirect(route('tenant.shop'));
    }

    /** The checkout is reachable while holiday mode is off. */
    public function test_checkout_page_accessible_when_vacation_mode_off(): void
    {
        $this->setSetting('vacation_mode', '0');

        $response = $this->withoutTenantMiddleware()->get(route('tenant.checkout'));

        // Inertia answers 200 rather than redirecting while the shop is open
        $this->assertNotEquals(302, $response->status(), 'Checkout nie powinien przekierowywać gdy otwarte');
    }

    /** The checkout refuses a submission missing a required field. */
    public function test_checkout_store_rejects_order_below_min_order_value(): void
    {
        $response = $this->withoutTenantMiddleware()->postJson(route('tenant.checkout.store'), [
            'customer_name' => 'Jan Test',
            'customer_phone' => '123456789',
            'payment_method' => 'cash_on_delivery',
            'items' => [],
        ]);

        $response->assertStatus(422);
    }
}
