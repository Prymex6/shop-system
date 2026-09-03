<?php

namespace Tests\Feature;

use App\Models\Tenant\Customer;
use App\Models\Tenant\Order;
use App\Models\Tenant\RmaRequest;
use Illuminate\Support\Str;
use Tests\TenantTestCase;

/**
 * Covers round 20's "guest dead-ends" findings:
 *   - guests who paid online for a digital product landed on a 404 after
 *     paying, because PaymentController::return() redirected to the
 *     tracking page without the token that page requires for guests
 *   - the RMA form (round 11: orphaned, no render route at all) also had
 *     no path for guests at all — rma.store sat behind auth.customer, with
 *     no order-lookup-by-token mechanism
 */
class GuestRmaAndDownloadAccessTest extends TenantTestCase
{
    private function deliveredOrder(array $overrides = []): Order
    {
        return $this->createTestOrder(array_merge([
            'status' => 'completed',
            'fulfillment_status' => 'delivered',
            'payment_status' => 'paid',
            'tracking_token' => Str::random(32),
        ], $overrides));
    }

    // ── Guest RMA access via tracking token ─────────────────────────────

    public function test_guest_can_view_rma_form_with_valid_tracking_token(): void
    {
        $order = $this->deliveredOrder();

        $response = $this->withoutTenantMiddleware()
            ->get(route('tenant.rma.create', $order->order_number) . '?token=' . $order->tracking_token);

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page->component('Tenant/Client/Rma/Create')
            ->where('order.id', $order->id)
        );
    }

    public function test_guest_cannot_view_rma_form_without_token(): void
    {
        $order = $this->deliveredOrder();

        $response = $this->withoutTenantMiddleware()
            ->get(route('tenant.rma.create', $order->order_number));

        $response->assertForbidden();
    }

    public function test_guest_cannot_view_rma_form_with_wrong_token(): void
    {
        $order = $this->deliveredOrder();

        $response = $this->withoutTenantMiddleware()
            ->get(route('tenant.rma.create', $order->order_number) . '?token=wrong-token');

        $response->assertForbidden();
    }

    // ── 14-day return window enforcement ─────────────────────────────────

    public function test_rma_is_rejected_after_14_day_return_window(): void
    {
        $order = $this->deliveredOrder(['delivered_at' => now()->subDays(20)]);
        $order->items()->create(['name' => 'Produkt', 'quantity' => 1, 'price' => 50, 'total' => 50]);

        $response = $this->withoutTenantMiddleware()
            ->post(route('tenant.rma.store', $order->order_number), [
                'token' => $order->tracking_token,
                'reason' => 'Za późno',
                'items' => [['name' => 'Produkt', 'qty' => 1]],
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('error');
        $this->assertDatabaseMissing('rma_requests', ['order_id' => $order->id]);
    }

    public function test_rma_is_accepted_within_14_day_return_window(): void
    {
        $order = $this->deliveredOrder(['delivered_at' => now()->subDays(5)]);
        $order->items()->create(['name' => 'Produkt', 'quantity' => 1, 'price' => 50, 'total' => 50]);

        $response = $this->withoutTenantMiddleware()
            ->post(route('tenant.rma.store', $order->order_number), [
                'token' => $order->tracking_token,
                'reason' => 'W terminie',
                'items' => [['name' => 'Produkt', 'qty' => 1]],
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('rma_requests', ['order_id' => $order->id]);
    }

    public function test_rma_create_page_flags_expired_return_window(): void
    {
        $order = $this->deliveredOrder(['delivered_at' => now()->subDays(20)]);

        $response = $this->withoutTenantMiddleware()
            ->get(route('tenant.rma.create', $order->order_number) . '?token=' . $order->tracking_token);

        $response->assertInertia(fn ($page) => $page->component('Tenant/Client/Rma/Create')
            ->where('return_window_expired', true)
        );
    }

    public function test_guest_can_submit_rma_with_valid_tracking_token(): void
    {
        $order = $this->deliveredOrder();
        $order->items()->create(['name' => 'Produkt', 'quantity' => 1, 'price' => 50, 'total' => 50]);

        $response = $this->withoutTenantMiddleware()
            ->post(route('tenant.rma.store', $order->order_number), [
                'token' => $order->tracking_token,
                'reason' => 'Produkt uszkodzony',
                'items' => [['name' => 'Produkt', 'qty' => 1]],
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('rma_requests', ['order_id' => $order->id, 'reason' => 'Produkt uszkodzony']);
    }

    public function test_guest_cannot_submit_rma_without_token_or_login(): void
    {
        $order = $this->deliveredOrder();

        $response = $this->withoutTenantMiddleware()
            ->post(route('tenant.rma.store', $order->order_number), [
                'reason' => 'Produkt uszkodzony',
                'items' => [['name' => 'Produkt', 'qty' => 1]],
            ]);

        $response->assertForbidden();
        $this->assertDatabaseMissing('rma_requests', ['order_id' => $order->id]);
    }

    // ── Logged-in customer path still works (regression) ────────────────

    public function test_logged_in_customer_can_submit_rma_without_token(): void
    {
        $customer = Customer::create(['name' => 'K', 'email' => 'k@test.com', 'password' => bcrypt('secret')]);
        $order = $this->deliveredOrder(['customer_id' => $customer->id]);
        $order->items()->create(['name' => 'Produkt', 'quantity' => 1, 'price' => 50, 'total' => 50]);

        $response = $this->actingAs($customer, 'customer')
            ->withoutTenantMiddleware()
            ->post(route('tenant.rma.store', $order->order_number), [
                'reason' => 'Nie pasuje',
                'items' => [['name' => 'Produkt', 'qty' => 1]],
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('rma_requests', ['order_id' => $order->id]);
    }

    public function test_customer_cannot_submit_rma_for_another_customers_order(): void
    {
        $owner = Customer::create(['name' => 'Owner', 'email' => 'owner@test.com', 'password' => bcrypt('secret')]);
        $other = Customer::create(['name' => 'Other', 'email' => 'other@test.com', 'password' => bcrypt('secret')]);
        $order = $this->deliveredOrder(['customer_id' => $owner->id]);

        $response = $this->actingAs($other, 'customer')
            ->withoutTenantMiddleware()
            ->post(route('tenant.rma.store', $order->order_number), [
                'reason' => 'Nie moje',
                'items' => [['name' => 'Produkt', 'qty' => 1]],
            ]);

        $response->assertForbidden();
    }

    public function test_duplicate_rma_request_is_rejected(): void
    {
        $order = $this->deliveredOrder();
        RmaRequest::create([
            'order_id' => $order->id, 'rma_number' => 'RMA-1', 'customer_email' => $order->customer_email,
            'customer_name' => $order->customer_name, 'status' => 'pending', 'reason' => 'x', 'items' => [],
        ]);

        $response = $this->withoutTenantMiddleware()
            ->post(route('tenant.rma.store', $order->order_number), [
                'token' => $order->tracking_token,
                'reason' => 'Ponowne zgłoszenie',
                'items' => [['name' => 'Produkt', 'qty' => 1]],
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('error');
        $this->assertEquals(1, RmaRequest::where('order_id', $order->id)->count());
    }

    // ── Payment return redirect carries the tracking token for guests ───

    public function test_payment_return_redirects_with_tracking_token(): void
    {
        $this->setSettings([
            'p24_merchant_id' => '123456', 'p24_pos_id' => '123456',
            'p24_api_key' => 'test-api-key', 'p24_crc' => 'test-crc-key', 'p24_mode' => 'sandbox',
        ]);

        $order = $this->createTestOrder([
            'payment_method' => 'przelewy24',
            'payment_status' => 'paid', // handleReturn() just re-checks current status for most gateways
            'tracking_token' => Str::random(32),
        ]);

        $response = $this->withoutTenantMiddleware()
            ->get(route('tenant.payment.return', $order->order_number) . '?token=' . $order->tracking_token . '&orderId=1');

        $response->assertRedirect();
        $this->assertStringContainsString('token=' . $order->tracking_token, $response->headers->get('Location'));
    }
}
