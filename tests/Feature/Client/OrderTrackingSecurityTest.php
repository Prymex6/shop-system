<?php

namespace Tests\Feature\Client;

use App\Models\Tenant\Customer;
use App\Models\Tenant\Order;
use Tests\TenantTestCase;

class OrderTrackingSecurityTest extends TenantTestCase
{
    private Customer $customer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->customer = Customer::create([
            'name' => 'Klient Testowy',
            'email' => 'klient@test.com',
            'password' => bcrypt('password'),
            'phone' => '123456789',
        ]);
    }

    private function makeOrder(array $overrides = []): Order
    {
        return $this->createTestOrder(array_merge([
            'customer_id' => $this->customer->id,
            'tracking_token' => 'VALID_TOKEN_32_CHARS_ABCDEFGHIJK',
            'payment_method' => 'cash_on_delivery',
            'payment_status' => 'pending',
        ], $overrides));
    }

    /** Tracking with a valid token answers 200. */
    public function test_tracking_with_valid_token_returns_200(): void
    {
        $order = $this->makeOrder();

        $response = $this->withoutTenantMiddleware()
            ->get(route('tenant.order.tracking', $order->order_number) . '?token=VALID_TOKEN_32_CHARS_ABCDEFGHIJK');

        $response->assertStatus(200);
    }

    /** Tracking with a wrong token and no session answers 403. */
    public function test_tracking_with_wrong_token_returns_403(): void
    {
        $order = $this->makeOrder();

        $response = $this->withoutTenantMiddleware()
            ->get(route('tenant.order.tracking', $order->order_number) . '?token=WRONG_TOKEN');

        $response->assertStatus(403);
    }

    /** Tracking with neither a token nor a session answers 404. */
    public function test_tracking_without_token_and_not_logged_in_returns_404(): void
    {
        $order = $this->makeOrder();

        $response = $this->withoutTenantMiddleware()
            ->get(route('tenant.order.tracking', $order->order_number));

        $response->assertStatus(404);
    }

    /** The customer who placed the order needs no token. */
    public function test_logged_in_owner_can_track_without_token(): void
    {
        $order = $this->makeOrder();

        $response = $this->withoutTenantMiddleware()
            ->actingAs($this->customer, 'customer')
            ->get(route('tenant.order.tracking', $order->order_number));

        $response->assertStatus(200);
    }

    /** A signed-in customer still cannot track an order belonging to somebody else. */
    public function test_logged_in_customer_cannot_track_someone_elses_order(): void
    {
        $other = Customer::create([
            'name' => 'Obcy Klient',
            'email' => 'obcy@test.com',
            'password' => bcrypt('password'),
            'phone' => '555555555',
        ]);

        $order = $this->makeOrder(); // należy do $this->customer

        $response = $this->withoutTenantMiddleware()
            ->actingAs($other, 'customer')
            ->get(route('tenant.order.tracking', $order->order_number));

        $response->assertStatus(404);
    }

    /** An order number that does not exist answers 404. */
    public function test_nonexistent_order_number_returns_404(): void
    {
        $response = $this->withoutTenantMiddleware()
            ->get(route('tenant.order.tracking', 'ORD-NIEISTNIEJE-9999') . '?token=cokolwiek');

        $response->assertStatus(404);
    }
}
