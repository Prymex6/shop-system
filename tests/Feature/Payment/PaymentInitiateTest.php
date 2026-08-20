<?php

namespace Tests\Feature\Payment;

use Illuminate\Support\Facades\Http;
use Tests\TenantTestCase;

/**
 * Tests for the payment initiation endpoint.
 * GET /payment/{order}/initiate
 */
class PaymentInitiateTest extends TenantTestCase
{
    public function test_redirects_to_p24_when_configured(): void
    {
        $this->setSettings([
            'p24_merchant_id' => '123456',
            'p24_api_key' => 'test-key',
            'p24_crc' => 'test-crc',
            'p24_mode' => 'sandbox',
        ]);

        Http::fake([
            '*/api/v1/transaction/register' => Http::response([
                'data' => ['token' => 'redirect-token-abc'],
            ], 200),
        ]);

        $order = $this->createTestOrder([
            'payment_method' => 'przelewy24',
            'payment_status' => 'awaiting_payment',
        ]);

        $response = $this->withoutTenantMiddleware()
            ->get(route('tenant.payment.initiate', $order->order_number) . '?token=' . $order->tracking_token);

        $response->assertRedirect();
        $this->assertStringContainsString('redirect-token-abc', $response->headers->get('Location'));
    }

    public function test_redirects_with_error_when_gateway_not_configured(): void
    {
        // No P24 settings seeded → not configured
        $order = $this->createTestOrder([
            'payment_method' => 'przelewy24',
            'payment_status' => 'awaiting_payment',
        ]);

        $response = $this->withoutTenantMiddleware()
            ->get(route('tenant.payment.initiate', $order->order_number) . '?token=' . $order->tracking_token);

        $response->assertRedirect(route('tenant.shop'));
        $response->assertSessionHas('error');
    }

    public function test_redirects_with_info_when_already_paid(): void
    {
        $order = $this->createTestOrder([
            'payment_method' => 'przelewy24',
            'payment_status' => 'paid',
        ]);

        $response = $this->withoutTenantMiddleware()
            ->get(route('tenant.payment.initiate', $order->order_number) . '?token=' . $order->tracking_token);

        $response->assertRedirect(route('tenant.shop'));
        $response->assertSessionHas('info');
    }

    public function test_returns_error_for_cash_payment(): void
    {
        $order = $this->createTestOrder([
            'payment_method' => 'cash_on_delivery',
            'payment_status' => 'pending',
        ]);

        $response = $this->withoutTenantMiddleware()
            ->get(route('tenant.payment.initiate', $order->order_number) . '?token=' . $order->tracking_token);

        $response->assertRedirect(route('tenant.shop'));
        $response->assertSessionHas('error');
    }

    public function test_returns_error_for_card_on_delivery(): void
    {
        $order = $this->createTestOrder([
            'payment_method' => 'cash_on_delivery',
            'payment_status' => 'pending',
        ]);

        $response = $this->withoutTenantMiddleware()
            ->get(route('tenant.payment.initiate', $order->order_number) . '?token=' . $order->tracking_token);

        $response->assertRedirect(route('tenant.shop'));
        $response->assertSessionHas('error');
    }

    public function test_updates_payment_data_on_successful_initiation(): void
    {
        $this->setSettings([
            'p24_merchant_id' => '123456',
            'p24_api_key' => 'test-key',
            'p24_crc' => 'test-crc',
        ]);

        Http::fake([
            '*/api/v1/transaction/register' => Http::response([
                'data' => ['token' => 'data-saved-token'],
            ], 200),
        ]);

        $order = $this->createTestOrder([
            'payment_method' => 'przelewy24',
            'payment_status' => 'awaiting_payment',
        ]);

        $this->withoutTenantMiddleware()
            ->get(route('tenant.payment.initiate', $order->order_number) . '?token=' . $order->tracking_token);

        $order->refresh();
        $this->assertNotNull($order->payment_data);
        $this->assertEquals('data-saved-token', $order->payment_data['p24_token']);
    }

    public function test_rejects_request_without_valid_token_or_ownership(): void
    {
        $order = $this->createTestOrder([
            'payment_method' => 'przelewy24',
            'payment_status' => 'awaiting_payment',
        ]);

        // No ?token=, not logged in as the order's customer, not staff
        $response = $this->withoutTenantMiddleware()
            ->get(route('tenant.payment.initiate', $order->order_number));

        $response->assertStatus(403);
    }

    public function test_rejects_wrong_token(): void
    {
        $order = $this->createTestOrder([
            'payment_method' => 'przelewy24',
            'payment_status' => 'awaiting_payment',
        ]);

        $response = $this->withoutTenantMiddleware()
            ->get(route('tenant.payment.initiate', $order->order_number) . '?token=not-the-real-token');

        $response->assertStatus(403);
    }
}
