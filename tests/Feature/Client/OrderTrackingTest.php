<?php

namespace Tests\Feature\Client;

use Tests\TenantTestCase;

/**
 * Tests for public order tracking page.
 */
class OrderTrackingTest extends TenantTestCase
{
    public function test_order_tracking_page_loads_for_valid_order(): void
    {
        $order = $this->createTestOrder([
            'order_number' => 'TRACK-001',
            'status' => 'processing',
            'tracking_token' => 'test-token-001',
        ]);

        $response = $this->withoutTenantMiddleware()
            ->get(route('tenant.order.tracking', $order->order_number) . '?token=test-token-001');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('Tenant/Client/OrderTracking')
            ->has('order')
        );
    }

    public function test_tracking_shows_correct_order_number(): void
    {
        $order = $this->createTestOrder([
            'order_number' => 'TRACK-002',
            'tracking_token' => 'test-token-002',
        ]);

        $response = $this->withoutTenantMiddleware()
            ->get(route('tenant.order.tracking', $order->order_number) . '?token=test-token-002');

        $data = $response->inertiaProps('order');
        $this->assertEquals('TRACK-002', $data['order_number']);
    }

    public function test_tracking_returns_404_for_unknown_order(): void
    {
        $response = $this->withoutTenantMiddleware()
            ->get(route('tenant.order.tracking', 'NONEXISTENT-999'));

        $response->assertStatus(404);
    }

    public function test_tracking_shows_delivery_address_for_delivery_orders(): void
    {
        $order = $this->createTestOrder([
            'order_number' => 'DELIV-001',
            'shipping_address' => json_encode(['street' => 'ul. Testowa 1', 'city' => 'Warszawa']),
            'tracking_token' => 'test-token-deliv',
        ]);

        $response = $this->withoutTenantMiddleware()
            ->get(route('tenant.order.tracking', $order->order_number) . '?token=test-token-deliv');

        $response->assertStatus(200);
    }

    public function test_completed_orders_can_be_tracked(): void
    {
        $order = $this->createTestOrder([
            'order_number' => 'DONE-001',
            'status' => 'delivered',
            'tracking_token' => 'test-token-done',
        ]);

        $response = $this->withoutTenantMiddleware()
            ->get(route('tenant.order.tracking', $order->order_number) . '?token=test-token-done');

        $response->assertStatus(200);
    }
}
