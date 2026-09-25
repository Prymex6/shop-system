<?php

namespace Tests\Feature\Client;

use App\Models\Tenant\Customer;
use App\Models\Tenant\Order;
use Carbon\Carbon;
use Tests\TenantTestCase;

class OrderCancelTest extends TenantTestCase
{
    private Customer $customer;

    private Customer $otherCustomer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->customer = Customer::create([
            'name' => 'Jan Test',
            'email' => 'jan@test.com',
            'password' => bcrypt('password'),
            'phone' => '123456789',
        ]);

        $this->otherCustomer = Customer::create([
            'name' => 'Inny Klient',
            'email' => 'inny@test.com',
            'password' => bcrypt('password'),
            'phone' => '987654321',
        ]);
    }

    private function makeOrder(array $overrides = []): Order
    {
        return $this->createTestOrder(array_merge([
            'customer_id' => $this->customer->id,
            'payment_method' => 'cash_on_delivery',
            'payment_status' => 'pending',
        ], $overrides));
    }

    /** A pending order placed a moment ago can be cancelled. */
    public function test_customer_can_cancel_own_pending_order_within_5_minutes(): void
    {
        Carbon::setTestNow(now()->subMinutes(2));
        $order = $this->makeOrder();
        Carbon::setTestNow();

        $response = $this->withoutTenantMiddleware()
            ->actingAs($this->customer, 'customer')
            ->delete("/moje-konto/zamowienia/{$order->order_number}/anuluj");

        $response->assertRedirect();
        $response->assertSessionHas('success');
        $this->assertDatabaseHas('orders', [
            'order_number' => $order->order_number,
            'status' => 'cancelled',
        ]);
    }

    /** An order already being prepared cannot be cancelled. */
    public function test_customer_cannot_cancel_preparing_order(): void
    {
        Carbon::setTestNow(now()->subMinutes(1));
        $order = $this->makeOrder(['status' => 'processing']);
        Carbon::setTestNow();

        $response = $this->withoutTenantMiddleware()
            ->actingAs($this->customer, 'customer')
            ->delete("/moje-konto/zamowienia/{$order->order_number}/anuluj");

        $response->assertSessionHasErrors('cancel');
        $this->assertDatabaseHas('orders', [
            'order_number' => $order->order_number,
            'status' => 'processing',
        ]);
    }

    /** After fifteen minutes the order can no longer be cancelled. */
    public function test_customer_cannot_cancel_order_after_5_minutes(): void
    {
        Carbon::setTestNow(now()->subMinutes(20));
        $order = $this->makeOrder();
        Carbon::setTestNow();

        $response = $this->withoutTenantMiddleware()
            ->actingAs($this->customer, 'customer')
            ->delete("/moje-konto/zamowienia/{$order->order_number}/anuluj");

        $response->assertSessionHasErrors('cancel');
        $this->assertDatabaseHas('orders', [
            'order_number' => $order->order_number,
            'status' => 'pending',
        ]);
    }

    /** An order belonging to somebody else answers 404 rather than cancelling. */
    public function test_customer_cannot_cancel_another_customers_order(): void
    {
        Carbon::setTestNow(now()->subMinutes(1));
        $order = $this->makeOrder();
        Carbon::setTestNow();

        $response = $this->withoutTenantMiddleware()
            ->actingAs($this->otherCustomer, 'customer')
            ->delete("/moje-konto/zamowienia/{$order->order_number}/anuluj");

        $response->assertStatus(404);
        $this->assertDatabaseHas('orders', [
            'order_number' => $order->order_number,
            'status' => 'pending',
        ]);
    }

    /** A signed-out visitor cannot cancel an order. */
    public function test_unauthenticated_user_cannot_cancel_order(): void
    {
        Carbon::setTestNow(now()->subMinutes(1));
        $order = $this->makeOrder();
        Carbon::setTestNow();

        $response = $this->withoutTenantMiddleware()
            ->delete("/moje-konto/zamowienia/{$order->order_number}/anuluj");

        // Either a redirect to sign in, or a 403
        $response->assertStatus(302);
        $this->assertDatabaseHas('orders', [
            'order_number' => $order->order_number,
            'status' => 'pending',
        ]);
    }
}
