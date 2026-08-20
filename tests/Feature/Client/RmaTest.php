<?php

namespace Tests\Feature\Client;

use App\Models\Tenant\Customer;
use Tests\TenantTestCase;

/**
 * Tests for customer RMA (return merchandise authorization) submission.
 */
class RmaTest extends TenantTestCase
{
    private function customer(): Customer
    {
        return Customer::create([
            'name' => 'Klient',
            'email' => 'klient@test.com',
            'password' => bcrypt('s'),
        ]);
    }

    public function test_guest_cannot_submit_rma_without_a_valid_tracking_token(): void
    {
        // rma.store is a public route now (guests with a valid order tracking
        // token can file a return too — see GuestRmaAndDownloadAccessTest),
        // so an unauthenticated request with no token is rejected with 403
        // from inside the controller rather than redirected to a login page.
        $order = $this->createTestOrder();

        $response = $this->withoutTenantMiddleware()
            ->post(route('tenant.rma.store', $order->order_number), [
                'reason' => 'Produkt uszkodzony',
                'items' => [['name' => 'Produkt', 'qty' => 1]],
            ]);

        $response->assertForbidden();
    }

    public function test_customer_can_submit_rma(): void
    {
        $customer = $this->customer();
        $order = $this->createTestOrder([
            'customer_email' => $customer->email,
            'customer_id' => $customer->id,
        ]);

        $response = $this->actingAs($customer, 'customer')
            ->withoutTenantMiddleware()
            ->post(route('tenant.rma.store', $order->order_number), [
                'reason' => 'Produkt uszkodzony',
                'items' => [['name' => 'Testowy Produkt', 'qty' => 1]],
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('rma_requests', [
            'order_id' => $order->id,
            'status' => 'pending',
        ]);
    }

    public function test_rma_requires_reason(): void
    {
        $customer = $this->customer();
        $order = $this->createTestOrder([
            'customer_email' => $customer->email,
            'customer_id' => $customer->id,
        ]);

        $response = $this->actingAs($customer, 'customer')
            ->withoutTenantMiddleware()
            ->postJson(route('tenant.rma.store', $order->order_number), [
                'items' => [['name' => 'Produkt', 'qty' => 1]],
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['reason']);
    }

    public function test_rma_requires_items(): void
    {
        $customer = $this->customer();
        $order = $this->createTestOrder([
            'customer_email' => $customer->email,
            'customer_id' => $customer->id,
        ]);

        $response = $this->actingAs($customer, 'customer')
            ->withoutTenantMiddleware()
            ->postJson(route('tenant.rma.store', $order->order_number), [
                'reason' => 'Produkt uszkodzony',
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['items']);
    }
}
