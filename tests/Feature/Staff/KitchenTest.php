<?php

namespace Tests\Feature\Staff;

use App\Models\Tenant\User;
use Tests\TenantTestCase;

/**
 * Tests for fulfillment staff panel (order processing).
 */
class KitchenTest extends TenantTestCase
{
    private function fulfillmentStaff(): User
    {
        return User::create([
            'name' => 'Fulfillment', 'email' => 'fulfillment@test.com',
            'password' => bcrypt('s'), 'role' => 'fulfillment', 'is_active' => true,
        ]);
    }

    public function test_fulfillment_staff_can_view_panel(): void
    {
        $response = $this->actingAs($this->fulfillmentStaff(), 'tenant')
            ->withoutTenantMiddleware()
            ->get(route('tenant.staff.fulfillment'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->has('orders')
        );
    }

    public function test_fulfillment_panel_shows_paid_orders(): void
    {
        $this->createTestOrder(['status' => 'confirmed', 'payment_status' => 'paid', 'fulfillment_status' => 'unfulfilled']);
        $this->createTestOrder(['status' => 'processing', 'payment_status' => 'paid', 'order_number' => 'ORD-2', 'fulfillment_status' => 'processing']);

        $response = $this->actingAs($this->fulfillmentStaff(), 'tenant')
            ->withoutTenantMiddleware()
            ->get(route('tenant.staff.fulfillment'));

        $response->assertStatus(200);
    }

    public function test_fulfillment_panel_does_not_show_fulfilled_orders(): void
    {
        $this->createTestOrder(['status' => 'delivered', 'payment_status' => 'paid', 'fulfillment_status' => 'delivered']);

        $response = $this->actingAs($this->fulfillmentStaff(), 'tenant')
            ->withoutTenantMiddleware()
            ->get(route('tenant.staff.fulfillment'));

        $response->assertInertia(fn ($page) => $page->count('orders', 0)
        );
    }

    public function test_manager_can_also_view_fulfillment_panel(): void
    {
        $manager = User::create([
            'name' => 'Manager', 'email' => 'mgr@test.com',
            'password' => bcrypt('s'), 'role' => 'manager', 'is_active' => true,
        ]);

        $response = $this->actingAs($manager, 'tenant')
            ->withoutTenantMiddleware()
            ->get(route('tenant.staff.fulfillment'));

        $response->assertStatus(200);
    }
}
