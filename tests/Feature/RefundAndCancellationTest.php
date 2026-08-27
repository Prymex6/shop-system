<?php

namespace Tests\Feature;

use App\Mail\Tenant\RefundProcessedMail;
use App\Models\Tenant\Customer;
use App\Models\Tenant\Order;
use App\Models\Tenant\Product;
use App\Models\Tenant\ProductVariant;
use App\Models\Tenant\Refund;
use App\Models\Tenant\User;
use App\Services\OrderCancellationService;
use App\Services\RefundService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Tests\TenantTestCase;

/**
 * Covers the batch of critical refund/cancellation-money bugs found in
 * rounds 13/19 of AUDIT-FINDINGS.md:
 *   - a gateway refund always charged the full order total (fixed above,
 *     in Przelewy24GatewayTest)
 *   - OrderCancellationService never checked whether the order already had
 *     a partial refund before issuing a fresh, full gateway refund
 *   - stock was restored to the base Product on cancellation even when the
 *     order line was for a specific variant, permanently draining variant stock
 *   - a failed gateway refund still got marked 'completed' in the DB
 *   - refunding a cash-on-delivery/bank-transfer order was impossible
 *     (PaymentGatewayFactory::make() threw before any refund logic ran)
 */
class RefundAndCancellationTest extends TenantTestCase
{
    private function configureP24(): void
    {
        $this->setSettings([
            'p24_merchant_id' => '123456',
            'p24_pos_id' => '123456',
            'p24_api_key' => 'test-api-key',
            'p24_crc' => 'test-crc-key',
            'p24_mode' => 'sandbox',
        ]);
    }

    private function manager(): User
    {
        return User::create([
            'name' => 'Manager', 'email' => 'mgr@test.com',
            'password' => bcrypt('secret'), 'role' => 'manager', 'is_active' => true,
        ]);
    }

    private function orderWithVariantItem(array $overrides = []): array
    {
        $product = Product::create([
            'name' => 'Koszulka', 'slug' => 'koszulka', 'price' => 50,
            'type' => 'physical', 'status' => 'active', 'track_stock' => true, 'stock_quantity' => 0,
        ]);
        $variant = ProductVariant::create([
            'product_id' => $product->id, 'sku' => 'KOSZ-M', 'attributes' => ['Rozmiar' => 'M'],
            'price' => 50, 'stock_quantity' => 3, 'is_active' => true,
        ]);

        $order = $this->createTestOrder(array_merge([
            'total' => 50.00, 'subtotal' => 50.00, 'payment_status' => 'paid', 'status' => 'paid',
        ], $overrides));

        $order->items()->create([
            'product_id' => $product->id, 'variant_id' => $variant->id,
            'name' => 'Koszulka (M)', 'quantity' => 1, 'price' => 50, 'total' => 50,
        ]);

        return [$order, $product, $variant];
    }

    // ── OrderCancellationService: variant-aware stock restore ──────────────

    public function test_cancellation_restores_variant_stock_not_base_product(): void
    {
        [$order, $product, $variant] = $this->orderWithVariantItem(['payment_method' => 'cash_on_delivery']);

        app(OrderCancellationService::class)->cancel($order);

        $this->assertEquals(4, $variant->fresh()->stock_quantity, 'variant stock should be restored');
        $this->assertEquals(0, $product->fresh()->stock_quantity, 'base product stock must NOT be touched');
    }

    // ── OrderCancellationService: offline payment refund ────────────────────

    public function test_cancelling_offline_paid_order_marks_refunded_without_gateway_call(): void
    {
        [$order] = $this->orderWithVariantItem(['payment_method' => 'cash_on_delivery']);

        $result = app(OrderCancellationService::class)->cancel($order);

        $this->assertTrue($result['success']);
        $this->assertEquals('refunded', $order->fresh()->payment_status);
    }

    // ── OrderCancellationService: double-refund guard ───────────────────────

    public function test_cancellation_skips_gateway_refund_when_already_fully_refunded_by_rma(): void
    {
        Http::fake(); // any gateway call here would be a bug — assert none happens

        [$order] = $this->orderWithVariantItem([
            'payment_method' => 'przelewy24',
            'payment_data' => ['p24_order_id' => 1, 'p24_session_id' => 's1'],
        ]);

        Refund::create(['order_id' => $order->id, 'amount' => 50.00, 'status' => 'completed']);

        $result = app(OrderCancellationService::class)->cancel($order);

        $this->assertTrue($result['success']);
        $this->assertEquals('refunded', $order->fresh()->payment_status);
        Http::assertNothingSent();
    }

    public function test_cancellation_only_refunds_remaining_amount_after_partial_rma_refund(): void
    {
        $this->configureP24();
        Http::fake([
            '*/api/v1/transaction/refund' => Http::response(['data' => [['orderId' => 1]]], 200),
        ]);

        [$order] = $this->orderWithVariantItem([
            'payment_method' => 'przelewy24',
            'total' => 100.00,
            'payment_data' => ['p24_order_id' => 1, 'p24_session_id' => 's1'],
        ]);

        Refund::create(['order_id' => $order->id, 'amount' => 30.00, 'status' => 'completed']);

        app(OrderCancellationService::class)->cancel($order);

        Http::assertSent(fn ($request) => ($request['refunds'][0]['amount'] ?? null) === 7000); // (100-30)*100 grosze
    }

    // ── RefundService: failed gateway refund must not be marked completed ──

    public function test_failed_gateway_refund_does_not_mark_refund_completed(): void
    {
        $this->configureP24();
        Http::fake([
            '*/api/v1/transaction/refund' => Http::response(['error' => 'declined'], 400),
        ]);

        [$order] = $this->orderWithVariantItem([
            'payment_method' => 'przelewy24',
            'payment_data' => ['p24_order_id' => 1, 'p24_session_id' => 's1'],
        ]);

        $refund = Refund::create(['order_id' => $order->id, 'amount' => 50.00, 'status' => 'approved']);

        try {
            app(RefundService::class)->process($refund);
            $this->fail('Expected an exception on gateway refund failure');
        } catch (\Exception $e) {
            // expected
        }

        $this->assertEquals('approved', $refund->fresh()->status, 'refund must stay retryable, not silently "completed"');
        $this->assertEquals('paid', $order->fresh()->payment_status);
    }

    public function test_offline_payment_refund_completes_without_gateway_call(): void
    {
        Mail::fake();
        Http::fake();

        [$order] = $this->orderWithVariantItem(['payment_method' => 'bank_transfer']);
        $refund = Refund::create(['order_id' => $order->id, 'amount' => 50.00, 'status' => 'approved']);

        $result = app(RefundService::class)->process($refund);

        $this->assertEquals('completed', $result->status);
        $this->assertEquals('refunded', $order->fresh()->payment_status);
        Http::assertNothingSent();
    }

    public function test_successful_refund_still_completes_and_notifies(): void
    {
        $this->configureP24();
        Mail::fake();
        Http::fake([
            '*/api/v1/transaction/refund' => Http::response(['data' => [['orderId' => 1]]], 200),
        ]);

        [$order] = $this->orderWithVariantItem([
            'payment_method' => 'przelewy24',
            'payment_data' => ['p24_order_id' => 1, 'p24_session_id' => 's1'],
        ]);
        $refund = Refund::create(['order_id' => $order->id, 'amount' => 50.00, 'status' => 'approved']);

        $result = app(RefundService::class)->process($refund);

        $this->assertEquals('completed', $result->status);
        $this->assertEquals('refunded', $order->fresh()->payment_status);
        Mail::assertQueued(RefundProcessedMail::class);
    }

    // ── OrderManagementController::updateStatus() cancellation path ────────

    public function test_manager_cancelling_order_restores_variant_stock(): void
    {
        [$order, $product, $variant] = $this->orderWithVariantItem([
            'payment_method' => 'cash_on_delivery', 'status' => 'pending', 'payment_status' => 'pending',
        ]);

        $response = $this->actingAs($this->manager(), 'tenant')
            ->withoutTenantMiddleware()
            ->patch(route('tenant.manager.orders.update-status', $order), ['status' => 'cancelled']);

        $response->assertRedirect();
        $this->assertEquals(4, $variant->fresh()->stock_quantity);
        $this->assertEquals(0, $product->fresh()->stock_quantity);
    }

    // ── AccountController::cancelOrder() (customer self-service) ────────────

    public function test_customer_cancelling_own_order_restores_variant_stock(): void
    {
        $customer = Customer::create([
            'name' => 'Klient', 'email' => 'klient@test.com', 'password' => bcrypt('secret'),
        ]);

        [$order, $product, $variant] = $this->orderWithVariantItem([
            'status' => 'pending', 'payment_status' => 'pending', 'customer_id' => $customer->id,
        ]);

        $response = $this->actingAs($customer, 'customer')
            ->withoutTenantMiddleware()
            ->delete(route('tenant.account.order.cancel', $order->order_number));

        $response->assertRedirect();
        $this->assertEquals(4, $variant->fresh()->stock_quantity);
        $this->assertEquals(0, $product->fresh()->stock_quantity);
    }
}
