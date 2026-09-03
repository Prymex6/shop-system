<?php

namespace Tests\Feature\Staff;

use App\Models\Tenant\User;
use Illuminate\Support\Facades\Event;
use Tests\TenantTestCase;

/**
 * Tests for fulfillment staff order status updates.
 */
class DriverTest extends TenantTestCase
{
    private function fulfillmentStaff(): User
    {
        return User::create([
            'name' => 'Fulfillment Staff', 'email' => 'fulfillment@test.com',
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

    public function test_fulfillment_panel_shows_active_orders(): void
    {
        $this->createTestOrder([
            'fulfillment_status' => 'processing',
            'status' => 'processing',
            'payment_status' => 'paid',
            'payment_method' => 'cash_on_delivery',
        ]);

        $response = $this->actingAs($this->fulfillmentStaff(), 'tenant')
            ->withoutTenantMiddleware()
            ->get(route('tenant.staff.fulfillment'));

        $response->assertStatus(200);
    }

    public function test_fulfillment_staff_can_update_fulfillment_status(): void
    {
        Event::fake();
        $staff = $this->fulfillmentStaff();
        $order = $this->createTestOrder([
            'fulfillment_status' => 'unfulfilled',
            'status' => 'confirmed',
            'payment_status' => 'paid',
            'payment_method' => 'cash_on_delivery',
        ]);

        $response = $this->actingAs($staff, 'tenant')
            ->withoutTenantMiddleware()
            ->patchJson(route('tenant.staff.fulfillment.update-status', $order), [
                'fulfillment_status' => 'processing',
            ]);

        $response->assertStatus(200);
        $order->refresh();
        $this->assertEquals('processing', $order->fulfillment_status);
    }

    public function test_fulfillment_staff_can_mark_order_shipped(): void
    {
        Event::fake();
        $staff = $this->fulfillmentStaff();
        $order = $this->createTestOrder([
            'fulfillment_status' => 'processing',
            'status' => 'processing',
            'payment_status' => 'paid',
            'payment_method' => 'cash_on_delivery',
        ]);

        $response = $this->actingAs($staff, 'tenant')
            ->withoutTenantMiddleware()
            ->patchJson(route('tenant.staff.fulfillment.update-status', $order), [
                'fulfillment_status' => 'shipped',
            ]);

        $response->assertStatus(200);
        $order->refresh();
        $this->assertEquals('shipped', $order->fulfillment_status);
    }

    public function test_status_update_requires_valid_fulfillment_status(): void
    {
        $staff = $this->fulfillmentStaff();
        $order = $this->createTestOrder(['status' => 'processing']);

        $response = $this->actingAs($staff, 'tenant')
            ->withoutTenantMiddleware()
            ->patchJson(route('tenant.staff.fulfillment.update-status', $order), []);

        $response->assertStatus(422);
    }
}
