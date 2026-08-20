<?php

namespace Tests\Unit\Payment;

use App\Services\Payment\Przelewy24Gateway;
use Illuminate\Support\Facades\Http;
use Tests\TenantTestCase;

class Przelewy24GatewayTest extends TenantTestCase
{
    private function makeConfiguredGateway(): Przelewy24Gateway
    {
        $this->setSettings([
            'p24_merchant_id' => '123456',
            'p24_pos_id' => '123456',
            'p24_api_key' => 'test-api-key',
            'p24_crc' => 'test-crc-key',
            'p24_mode' => 'sandbox',
        ]);

        return new Przelewy24Gateway;
    }

    public function test_is_not_configured_without_settings(): void
    {
        $gateway = new Przelewy24Gateway;
        $this->assertFalse($gateway->isConfigured());
    }

    public function test_is_configured_with_all_required_settings(): void
    {
        $gateway = $this->makeConfiguredGateway();
        $this->assertTrue($gateway->isConfigured());
    }

    public function test_is_not_configured_with_only_merchant_id(): void
    {
        $this->setSetting('p24_merchant_id', '123456');
        $gateway = new Przelewy24Gateway;
        $this->assertFalse($gateway->isConfigured());
    }

    public function test_create_payment_returns_error_when_not_configured(): void
    {
        $order = $this->createTestOrder();
        $gateway = new Przelewy24Gateway;

        $result = $gateway->createPayment($order);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('nie jest skonfigurowane', $result['error']);
    }

    public function test_create_payment_registers_transaction_and_returns_url(): void
    {
        Http::fake([
            '*/api/v1/transaction/register' => Http::response([
                'data' => ['token' => 'test-p24-token-abc123'],
            ], 200),
        ]);

        $gateway = $this->makeConfiguredGateway();
        $order = $this->createTestOrder(['payment_method' => 'przelewy24']);

        $result = $gateway->createPayment($order);

        $this->assertTrue($result['success']);
        $this->assertStringContainsString('test-p24-token-abc123', $result['payment_url']);
        $this->assertStringContainsString('trnRequest', $result['payment_url']);
    }

    public function test_create_payment_saves_token_to_order(): void
    {
        Http::fake([
            '*/api/v1/transaction/register' => Http::response([
                'data' => ['token' => 'saved-token-xyz'],
            ], 200),
        ]);

        $gateway = $this->makeConfiguredGateway();
        $order = $this->createTestOrder(['payment_method' => 'przelewy24']);

        $gateway->createPayment($order);

        $order->refresh();
        $this->assertNotNull($order->payment_data);
        $this->assertEquals('saved-token-xyz', $order->payment_data['p24_token']);
        $this->assertArrayHasKey('p24_session_id', $order->payment_data);
    }

    public function test_create_payment_returns_error_on_api_failure(): void
    {
        Http::fake([
            '*/api/v1/transaction/register' => Http::response([
                'error' => 'Invalid credentials',
            ], 401),
        ]);

        $gateway = $this->makeConfiguredGateway();
        $order = $this->createTestOrder();

        $result = $gateway->createPayment($order);

        $this->assertFalse($result['success']);
    }

    public function test_webhook_rejects_invalid_signature(): void
    {
        $gateway = $this->makeConfiguredGateway();

        $result = $gateway->handleWebhook([
            'sessionId' => 'fake-session',
            'orderId' => '999',
            'amount' => 5000,
            'currency' => 'PLN',
            'sign' => 'invalid-signature',
        ]);

        $this->assertFalse($result);
    }

    public function test_webhook_returns_false_for_unknown_order(): void
    {
        $gateway = $this->makeConfiguredGateway();

        // Calculate valid signature for the data
        $sign = hash('sha384', json_encode([
            'sessionId' => 'nonexistent-session',
            'orderId' => 42,
            'amount' => 5000,
            'currency' => 'PLN',
            'crc' => 'test-crc-key',
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

        $result = $gateway->handleWebhook([
            'sessionId' => 'nonexistent-session',
            'orderId' => 42,
            'amount' => 5000,
            'currency' => 'PLN',
            'sign' => $sign,
        ]);

        $this->assertFalse($result);
    }

    public function test_webhook_marks_order_paid_on_valid_data(): void
    {
        Http::fake([
            '*/api/v1/transaction/verify' => Http::response([
                'data' => ['status' => 'success'],
            ], 200),
        ]);

        $gateway = $this->makeConfiguredGateway();

        $sessionId = 'TEST-1234_' . time();
        $order = $this->createTestOrder([
            'payment_method' => 'przelewy24',
            'payment_status' => 'awaiting_payment',
            'payment_data' => ['p24_token' => 'tok', 'p24_session_id' => $sessionId],
        ]);

        $sign = hash('sha384', json_encode([
            'sessionId' => $sessionId,
            'orderId' => 88,
            'amount' => 5000,
            'currency' => 'PLN',
            'crc' => 'test-crc-key',
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

        $result = $gateway->handleWebhook([
            'sessionId' => $sessionId,
            'orderId' => 88,
            'amount' => 5000,
            'currency' => 'PLN',
            'sign' => $sign,
        ]);

        $this->assertTrue($result);
        $order->refresh();
        $this->assertEquals('paid', $order->payment_status);
        $this->assertNotNull($order->paid_at);
    }

    /**
     * refund() used to always charge back the order's full total regardless
     * of the amount requested — a "partial" refund from RefundService/
     * OrderCancellationService was actually a full refund at the real
     * gateway, decoupling the DB's refund bookkeeping from real money moved.
     */
    public function test_refund_sends_the_requested_partial_amount_not_order_total(): void
    {
        Http::fake([
            '*/api/v1/transaction/refund' => Http::response(['data' => [['orderId' => 1]]], 200),
        ]);

        $gateway = $this->makeConfiguredGateway();
        $order = $this->createTestOrder([
            'payment_method' => 'przelewy24',
            'total' => 100.00,
            'payment_data' => ['p24_order_id' => 555, 'p24_session_id' => 'sess-1'],
        ]);

        $result = $gateway->refund($order, 25.00);

        $this->assertTrue($result['success']);
        Http::assertSent(function ($request) {
            return str_contains($request->url(), '/api/v1/transaction/refund')
                && $request['refunds'][0]['amount'] === 2500; // grosze, not 10000
        });
    }
}
