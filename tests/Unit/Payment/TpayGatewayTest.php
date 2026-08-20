<?php

namespace Tests\Unit\Payment;

use App\Services\Payment\TpayGateway;
use Mockery;
use Tests\TenantTestCase;
use Tpay\OpenApi\Api\TpayApi;

class TpayGatewayTest extends TenantTestCase
{
    public function test_is_not_configured_without_settings(): void
    {
        $gateway = new TpayGateway;
        $this->assertFalse($gateway->isConfigured());
    }

    public function test_is_not_configured_with_only_client_id(): void
    {
        $this->setSetting('tpay_client_id', 'some-id');
        $gateway = new TpayGateway;
        $this->assertFalse($gateway->isConfigured());
    }

    public function test_is_configured_with_client_id_and_secret(): void
    {
        $this->setSettings([
            'tpay_client_id' => 'my-client-id',
            'tpay_client_secret' => 'my-client-secret',
            'tpay_mode' => 'sandbox',
        ]);

        $gateway = new TpayGateway;
        $this->assertTrue($gateway->isConfigured());
    }

    public function test_create_payment_returns_error_when_not_configured(): void
    {
        $gateway = new TpayGateway;
        $order = $this->createTestOrder(['payment_method' => 'tpay']);

        $result = $gateway->createPayment($order);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('nie jest skonfigurowane', $result['error']);
    }

    public function test_create_payment_saves_transaction_id(): void
    {
        // Build mock TpayApi with transactions()->createTransaction() chain
        $mockTransaction = Mockery::mock();
        $mockTransaction->transactionId = 'tpay-txn-abc123';
        $mockTransaction->transactionPaymentUrl = 'https://secure.tpay.com/tpay-txn-abc123';

        $mockTransactionsApi = Mockery::mock();
        $mockTransactionsApi->shouldReceive('createTransaction')
            ->once()
            ->andReturn($mockTransaction);

        $mockApi = Mockery::mock(TpayApi::class);
        $mockApi->shouldReceive('transactions')->andReturn($mockTransactionsApi);

        // Inject mock directly – no alias mock needed
        $gateway = new TpayGateway($mockApi);
        $order = $this->createTestOrder(['payment_method' => 'tpay']);

        $result = $gateway->createPayment($order);

        $this->assertTrue($result['success']);
        $this->assertEquals('https://secure.tpay.com/tpay-txn-abc123', $result['payment_url']);

        $order->refresh();
        $this->assertNotNull($order->payment_data);
        $this->assertEquals('tpay-txn-abc123', $order->payment_data['tpay_transaction_id']);
    }

    // Regression: handleWebhook() must verify the Tpay JWS signature before
    // trusting anything in the payload — an unsigned "correct"/paid
    // notification (exactly what an attacker would send) must never mark
    // the order paid, no matter how legitimate the transaction ID looks.
    public function test_webhook_does_not_mark_order_paid_without_valid_signature(): void
    {
        $this->setSettings([
            'tpay_client_id' => 'test-id',
            'tpay_client_secret' => 'test-secret',
            'tpay_notification_secret' => 'test-notification-secret',
        ]);

        $gateway = new TpayGateway;

        $transactionId = 'tpay-txn-' . rand(1000, 9999);
        $order = $this->createTestOrder([
            'payment_method' => 'tpay',
            'payment_status' => 'awaiting_payment',
            'payment_data' => ['tpay_transaction_id' => $transactionId],
        ]);

        $result = $gateway->handleWebhook([
            'id' => $transactionId,
            'status' => 'correct',
        ]);

        $this->assertFalse($result);
        $order->refresh();
        $this->assertEquals('awaiting_payment', $order->payment_status);
        $this->assertNull($order->paid_at);
    }

    // Same regression, for the failure/chargeback path — an unsigned request
    // must not be able to flip payment_status either.
    public function test_webhook_does_not_change_status_without_valid_signature(): void
    {
        $this->setSettings([
            'tpay_client_id' => 'test-id',
            'tpay_client_secret' => 'test-secret',
            'tpay_notification_secret' => 'test-notification-secret',
        ]);

        $gateway = new TpayGateway;
        $transactionId = 'tpay-txn-failed-' . rand();
        $order = $this->createTestOrder([
            'payment_method' => 'tpay',
            'payment_status' => 'awaiting_payment',
            'payment_data' => ['tpay_transaction_id' => $transactionId],
        ]);

        $result = $gateway->handleWebhook(['id' => $transactionId, 'status' => 'err']);

        $this->assertFalse($result);
        $order->refresh();
        $this->assertEquals('awaiting_payment', $order->payment_status);
    }

    public function test_webhook_returns_false_for_unknown_transaction(): void
    {
        $gateway = new TpayGateway;

        $result = $gateway->handleWebhook([
            'id' => 'nonexistent-transaction-xyz',
            'status' => 'correct',
        ]);

        $this->assertFalse($result);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
