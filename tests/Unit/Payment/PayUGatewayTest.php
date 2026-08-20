<?php

namespace Tests\Unit\Payment;

use App\Services\Payment\PayUGateway;
use Illuminate\Http\Request;
use Mockery;
use Tests\TenantTestCase;

class PayUGatewayTest extends TenantTestCase
{
    private function makeConfiguredGateway(): PayUGateway
    {
        $this->setSettings([
            'payu_pos_id' => '300746',
            'payu_signature_key' => 'test-md5-key',
            'payu_client_id' => 'test-client-id',
            'payu_client_secret' => 'test-client-secret',
            'payu_mode' => 'sandbox',
        ]);

        return new PayUGateway;
    }

    public function test_is_not_configured_without_settings(): void
    {
        $gateway = new PayUGateway;
        $this->assertFalse($gateway->isConfigured());
    }

    public function test_is_configured_with_all_required_settings(): void
    {
        $gateway = $this->makeConfiguredGateway();
        $this->assertTrue($gateway->isConfigured());
    }

    public function test_is_not_configured_with_partial_settings(): void
    {
        $this->setSettings([
            'payu_pos_id' => '300746',
            'payu_client_id' => 'test-client-id',
            // missing signature_key and client_secret
        ]);

        $gateway = new PayUGateway;
        $this->assertFalse($gateway->isConfigured());
    }

    public function test_create_payment_returns_error_when_not_configured(): void
    {
        $gateway = new PayUGateway;
        $order = $this->createTestOrder(['payment_method' => 'payu']);

        $result = $gateway->createPayment($order);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('nie jest skonfigurowane', $result['error']);
    }

    public function test_create_payment_calls_openpayu_create(): void
    {
        $gateway = $this->makeConfiguredGateway();
        $order = $this->createTestOrder(['payment_method' => 'payu']);

        // Mock OpenPayU_Order::create() - static alias mock
        $mockResult = Mockery::mock('overload:OpenPayU_Result');
        $mockResult->shouldReceive('getStatus')->andReturn('SUCCESS');
        $mockResult->shouldReceive('getResponse')->andReturn((object) [
            'orderId' => 'PAYU-ORDER-123',
            'redirectUri' => 'https://secure.payu.com/pay/PAYU-ORDER-123',
        ]);

        $mockOrder = Mockery::mock('alias:OpenPayU_Order');
        $mockOrder->shouldReceive('create')->once()->andReturn($mockResult);

        $result = $gateway->createPayment($order);

        $this->assertTrue($result['success']);
        $this->assertEquals('https://secure.payu.com/pay/PAYU-ORDER-123', $result['payment_url']);
    }

    public function test_handle_return_checks_order_status(): void
    {
        $gateway = $this->makeConfiguredGateway();

        // Order not paid → handleReturn returns false
        $order = $this->createTestOrder([
            'payment_method' => 'payu',
            'payment_status' => 'awaiting_payment',
        ]);

        $request = $this->app->make(Request::class);
        $result = $gateway->handleReturn($request, $order);

        $this->assertFalse($result);
    }

    public function test_handle_return_returns_true_if_order_already_paid(): void
    {
        $gateway = $this->makeConfiguredGateway();

        $order = $this->createTestOrder([
            'payment_method' => 'payu',
            'payment_status' => 'paid',
        ]);

        $request = $this->app->make(Request::class);
        $result = $gateway->handleReturn($request, $order);

        $this->assertTrue($result);
    }

    public function test_webhook_handles_completed_status(): void
    {
        $gateway = $this->makeConfiguredGateway();

        $order = $this->createTestOrder([
            'payment_method' => 'payu',
            'payment_status' => 'awaiting_payment',
        ]);

        // Mock OpenPayU_Order::consumeNotification
        $mockResponse = (object) [
            'order' => (object) [
                'status' => 'COMPLETED',
                'extOrderId' => $order->order_number,
            ],
        ];

        $mockResult = Mockery::mock();
        $mockResult->shouldReceive('getResponse')->andReturn($mockResponse);

        $mockOrder = Mockery::mock('alias:OpenPayU_Order');
        $mockOrder->shouldReceive('consumeNotification')->once()->andReturn($mockResult);

        $result = $gateway->handleWebhook(['notification' => 'data']);

        $this->assertTrue($result);
        $order->refresh();
        $this->assertEquals('paid', $order->payment_status);
    }

    public function test_webhook_handles_cancelled_status(): void
    {
        $gateway = $this->makeConfiguredGateway();

        $order = $this->createTestOrder([
            'payment_method' => 'payu',
            'payment_status' => 'awaiting_payment',
        ]);

        $mockResponse = (object) [
            'order' => (object) [
                'status' => 'CANCELED',
                'extOrderId' => $order->order_number,
            ],
        ];

        $mockResult = Mockery::mock();
        $mockResult->shouldReceive('getResponse')->andReturn($mockResponse);

        $mockOrder = Mockery::mock('alias:OpenPayU_Order');
        $mockOrder->shouldReceive('consumeNotification')->once()->andReturn($mockResult);

        $gateway->handleWebhook([]);

        $order->refresh();
        $this->assertEquals('failed', $order->payment_status);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
