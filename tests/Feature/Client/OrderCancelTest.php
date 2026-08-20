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

    /** Można anulować zamówienie pending złożone przed chwilą */
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

    /** Nie można anulować zamówienia w stanie "preparing" */
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

    /** Nie można anulować zamówienia po upływie 15 minut */
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

    /** Nie można anulować cudzego zamówienia – powinno zwrócić 404 */
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

    /** Niezalogowany użytkownik nie może anulować zamówienia */
    public function test_unauthenticated_user_cannot_cancel_order(): void
    {
        Carbon::setTestNow(now()->subMinutes(1));
        $order = $this->makeOrder();
        Carbon::setTestNow();

        $response = $this->withoutTenantMiddleware()
            ->delete("/moje-konto/zamowienia/{$order->order_number}/anuluj");

        // Przekierowanie do logowania lub 403
        $response->assertStatus(302);
        $this->assertDatabaseHas('orders', [
            'order_number' => $order->order_number,
            'status' => 'pending',
        ]);
    }
}
