<?php

namespace Tests\Feature\Manager;

use App\Models\Tenant\User;
use Illuminate\Support\Facades\Event;
use Tests\TenantTestCase;

/**
 * Tests for manager order management (list, show, status updates).
 */
class OrderManagementTest extends TenantTestCase
{
    private function manager(): User
    {
        return User::create([
            'name' => 'Manager', 'email' => 'manager@test.com',
            'password' => bcrypt('secret'), 'role' => 'manager', 'is_active' => true,
        ]);
    }

    public function test_manager_can_view_orders_list(): void
    {
        $response = $this->actingAs($this->manager(), 'tenant')
            ->withoutTenantMiddleware()
            ->get(route('tenant.manager.orders.index'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('Tenant/Manager/Orders/Index')
            ->has('orders')
        );
    }

    public function test_orders_list_shows_all_statuses(): void
    {
        foreach (['pending', 'confirmed', 'processing', 'shipped', 'delivered'] as $status) {
            $this->createTestOrder(['status' => $status, 'order_number' => 'ORD-' . $status]);
        }

        $response = $this->actingAs($this->manager(), 'tenant')
            ->withoutTenantMiddleware()
            ->get(route('tenant.manager.orders.index'));

        $response->assertStatus(200);
    }

    public function test_manager_can_view_order_detail(): void
    {
        $order = $this->createTestOrder();

        $response = $this->actingAs($this->manager(), 'tenant')
            ->withoutTenantMiddleware()
            ->get(route('tenant.manager.orders.show', $order));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('Tenant/Manager/Orders/Show')
        );
    }

    public function test_manager_can_update_order_status(): void
    {
        Event::fake();
        $order = $this->createTestOrder(['status' => 'pending', 'payment_status' => 'paid']);

        $response = $this->actingAs($this->manager(), 'tenant')
            ->withoutTenantMiddleware()
            ->patchJson(route('tenant.manager.orders.update-status', $order), [
                'status' => 'processing',
            ]);

        $response->assertRedirect();
        $order->refresh();
        $this->assertEquals('processing', $order->status);
    }

    public function test_status_update_validates_allowed_statuses(): void
    {
        $order = $this->createTestOrder();

        $response = $this->actingAs($this->manager(), 'tenant')
            ->withoutTenantMiddleware()
            ->patchJson(route('tenant.manager.orders.update-status', $order), [
                'status' => 'invalid_status',
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['status']);
    }

    public function test_orders_can_be_filtered_by_status(): void
    {
        $this->createTestOrder(['status' => 'pending']);
        $this->createTestOrder(['status' => 'delivered', 'order_number' => 'ORD-2']);

        $response = $this->actingAs($this->manager(), 'tenant')
            ->withoutTenantMiddleware()
            ->get(route('tenant.manager.orders.index') . '?status=pending');

        $response->assertStatus(200);
    }
}
