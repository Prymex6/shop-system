<?php

namespace Tests\Feature\Payment;

use Illuminate\Support\Facades\Http;
use Tests\TenantTestCase;

/**
 * Tests for the return URL after payment.
 * GET /payment/{order}/return
 */
class PaymentReturnTest extends TenantTestCase
{
    public function test_p24_return_redirects_to_tracking_on_success(): void
    {
        $this->setSettings([
            'p24_merchant_id' => '123456',
            'p24_pos_id' => '123456',
            'p24_api_key' => 'test-key',
            'p24_crc' => 'test-crc',
        ]);

        Http::fake([
            '*/api/v1/transaction/verify' => Http::response([
                'data' => ['status' => 'success'],
            ], 200),
        ]);

        $sessionId = 'return-test-session-' . time();
        $order = $this->createTestOrder([
            'payment_method' => 'przelewy24',
            'payment_status' => 'awaiting_payment',
            'payment_data' => ['p24_token' => 'tok', 'p24_session_id' => $sessionId],
        ]);

        $response = $this->withoutTenantMiddleware()
            ->get(route('tenant.payment.return', $order->order_number) . '?token=' . $order->tracking_token . '&orderId=999&sessionId=' . $sessionId);

        // Must carry the tracking token — guests (no session) would 404 on the
        // tracking page otherwise, since it requires the token to identify them.
        $response->assertRedirect(route('tenant.order.tracking', $order->order_number) . '?token=' . $order->tracking_token);
    }

    public function test_p24_return_renders_error_page_on_verify_failure(): void
    {
        $this->setSettings([
            'p24_merchant_id' => '123456',
            'p24_pos_id' => '123456',
            'p24_api_key' => 'test-key',
            'p24_crc' => 'test-crc',
        ]);

        Http::fake([
            '*/api/v1/transaction/verify' => Http::response([
                'data' => ['status' => 'failed'],
            ], 200),
        ]);

        $sessionId = 'fail-session-' . time();
        $order = $this->createTestOrder([
            'payment_method' => 'przelewy24',
            'payment_status' => 'awaiting_payment',
            'payment_data' => ['p24_token' => 'tok', 'p24_session_id' => $sessionId],
        ]);

        $response = $this->withoutTenantMiddleware()
            ->get(route('tenant.payment.return', $order->order_number) . '?token=' . $order->tracking_token . '&orderId=1&sessionId=' . $sessionId);

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('Tenant/Client/PaymentResult')
            ->where('success', false)
        );
    }

    public function test_p24_return_marks_order_as_paid_on_success(): void
    {
        $this->setSettings([
            'p24_merchant_id' => '123456',
            'p24_pos_id' => '123456',
            'p24_api_key' => 'test-key',
            'p24_crc' => 'test-crc',
        ]);

        Http::fake([
            '*/api/v1/transaction/verify' => Http::response([
                'data' => ['status' => 'success'],
            ], 200),
        ]);

        $sessionId = 'mark-paid-session-' . time();
        $order = $this->createTestOrder([
            'payment_method' => 'przelewy24',
            'payment_status' => 'awaiting_payment',
            'payment_data' => ['p24_token' => 'tok', 'p24_session_id' => $sessionId],
        ]);

        $this->withoutTenantMiddleware()
            ->get(route('tenant.payment.return', $order->order_number) . '?token=' . $order->tracking_token . '&orderId=55&sessionId=' . $sessionId);

        $order->refresh();
        $this->assertEquals('paid', $order->payment_status);
        $this->assertNotNull($order->paid_at);
    }

    protected function tearDown(): void
    {
        \Mockery::close();
        parent::tearDown();
    }
}
