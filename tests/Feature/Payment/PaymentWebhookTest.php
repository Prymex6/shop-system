<?php

namespace Tests\Feature\Payment;

use App\Models\Tenant\Order;
use Illuminate\Support\Facades\Http;
use Mockery;
use Tests\TenantTestCase;

/**
 * Tests for all payment gateway webhook endpoints.
 * Uses withoutMiddleware() to bypass tenant domain detection.
 */
class PaymentWebhookTest extends TenantTestCase
{
    // ─── Przelewy24 Webhook ──────────────────────────────────────────

    public function test_p24_webhook_returns_200_and_ok(): void
    {
        $this->setSettings([
            'p24_merchant_id' => '123456',
            'p24_api_key' => 'test-key',
            'p24_crc' => 'test-crc',
        ]);

        Http::fake([
            '*/api/v1/transaction/verify' => Http::response(['data' => ['status' => 'success']], 200),
        ]);

        $sessionId = 'webhook-test-session-' . time();
        $order = $this->createTestOrder([
            'payment_method' => 'przelewy24',
            'payment_status' => 'awaiting_payment',
            'payment_data' => ['p24_token' => 'tok', 'p24_session_id' => $sessionId],
        ]);

        $sign = hash('sha384', json_encode([
            'sessionId' => $sessionId,
            'orderId' => 777,
            'amount' => 5000,
            'currency' => 'PLN',
            'crc' => 'test-crc',
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

        $response = $this->withoutTenantMiddleware()
            ->postJson(route('tenant.payment.webhook'), [
                'sessionId' => $sessionId,
                'orderId' => 777,
                'amount' => 5000,
                'currency' => 'PLN',
                'sign' => $sign,
            ]);

        $response->assertStatus(200);
        $response->assertSee('OK');
    }

    public function test_p24_webhook_returns_400_on_invalid_signature(): void
    {
        $this->setSettings([
            'p24_merchant_id' => '123456',
            'p24_api_key' => 'test-key',
            'p24_crc' => 'test-crc',
        ]);

        $response = $this->withoutTenantMiddleware()
            ->postJson(route('tenant.payment.webhook'), [
                'sessionId' => 'test-session',
                'orderId' => 1,
                'amount' => 5000,
                'currency' => 'PLN',
                'sign' => 'completely-wrong-signature',
            ]);

        $response->assertStatus(400);
    }

    // ─── PayU Webhook ────────────────────────────────────────────────

    public function test_payu_webhook_acknowledges_with_200(): void
    {
        $this->setSettings([
            'payu_pos_id' => '300746',
            'payu_signature_key' => 'test-md5',
            'payu_client_id' => 'test-id',
            'payu_client_secret' => 'test-secret',
        ]);

        $order = $this->createTestOrder([
            'payment_method' => 'payu',
            'payment_status' => 'awaiting_payment',
        ]);

        $mockResponse = (object) [
            'order' => (object) [
                'status' => 'COMPLETED',
                'extOrderId' => $order->order_number,
            ],
        ];
        $mockResult = Mockery::mock();
        $mockResult->shouldReceive('getResponse')->andReturn($mockResponse);
        Mockery::mock('alias:OpenPayU_Order')
            ->shouldReceive('consumeNotification')->andReturn($mockResult);

        $response = $this->withoutTenantMiddleware()
            ->postJson(route('tenant.payment.webhook.payu'), ['notification' => 'data']);

        $response->assertStatus(200);
        $response->assertJson(['status' => 'OK']);
    }

    // ─── Tpay Webhook ────────────────────────────────────────────────

    /**
     * Regression test for the unauthenticated-webhook vulnerability: a Tpay
     * notification with no (or an invalid) JWS signature must NOT be trusted,
     * even if it names a real order and claims "correct"/paid status.
     */
    public function test_tpay_webhook_rejects_unsigned_notification(): void
    {
        $this->setSettings([
            'tpay_client_id' => 'test-id',
            'tpay_client_secret' => 'test-secret',
            'tpay_notification_secret' => 'notif-secret',
        ]);

        $transactionId = 'tpay-webhook-' . rand();
        $order = $this->createTestOrder([
            'payment_method' => 'tpay',
            'payment_status' => 'awaiting_payment',
            'payment_data' => ['tpay_transaction_id' => $transactionId],
        ]);

        $response = $this->withoutTenantMiddleware()
            ->postJson(route('tenant.payment.webhook.tpay'), [
                'id' => $transactionId,
                'tr_id' => $transactionId,
                'status' => 'correct',
            ]);

        $response->assertStatus(400);
        $response->assertJson(['result' => '0']);

        $order->refresh();
        $this->assertEquals('awaiting_payment', $order->payment_status);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
