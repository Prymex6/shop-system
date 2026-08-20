<?php

namespace Tests\Feature\Client;

use App\Models\Tenant\Customer;
use Tests\TenantTestCase;

/**
 * Tests for PDF invoice generation.
 */
class InvoiceTest extends TenantTestCase
{
    private function customer(): Customer
    {
        return Customer::create([
            'name' => 'Jan Kowalski',
            'email' => 'jan@test.com',
            'phone' => '123456789',
        ]);
    }

    public function test_customer_can_view_their_invoice(): void
    {
        $customer = $this->customer();
        $order = $this->createTestOrder([
            'customer_id' => $customer->id,
            'customer_email' => 'jan@test.com',
            'payment_status' => 'paid',
        ]);

        $response = $this->actingAs($customer, 'customer')
            ->withoutTenantMiddleware()
            ->get(route('tenant.order.invoice', $order->order_number));

        $response->assertStatus(200);
    }

    public function test_invoice_contains_restaurant_details(): void
    {
        $this->setSettings([
            'shop_name' => 'Pizza Testowa',
            'shop_address' => 'ul. Główna 1',
        ]);
        $customer = $this->customer();
        $order = $this->createTestOrder([
            'customer_id' => $customer->id,
            'customer_email' => 'jan@test.com',
            'payment_status' => 'paid',
        ]);

        $response = $this->actingAs($customer, 'customer')
            ->withoutTenantMiddleware()
            ->get(route('tenant.order.invoice', $order->order_number));

        $response->assertStatus(200);
        $response->assertSee('Pizza Testowa');
    }

    public function test_invoice_returns_404_for_unknown_order(): void
    {
        $customer = $this->customer();

        $response = $this->actingAs($customer, 'customer')
            ->withoutTenantMiddleware()
            ->get(route('tenant.order.invoice', 'NONEXISTENT'));

        $response->assertStatus(404);
    }

    public function test_customer_cannot_view_other_customers_invoice(): void
    {
        $customer1 = $this->customer();
        $customer2 = Customer::create([
            'name' => 'Klient2', 'email' => 'klient2@test.com', 'phone' => '999888777',
        ]);
        $order = $this->createTestOrder([
            'customer_id' => $customer2->id,
            'customer_email' => 'klient2@test.com',
            'payment_status' => 'paid',
        ]);

        $response = $this->actingAs($customer1, 'customer')
            ->withoutTenantMiddleware()
            ->get(route('tenant.order.invoice', $order->order_number));

        // Should return 403 or 404 for wrong customer
        $this->assertNotEquals(200, $response->getStatusCode());
    }
}
