<?php

namespace Tests\Feature\Manager;

use App\Models\Tenant\AbandonedCart;
use App\Models\Tenant\User;
use Tests\TenantTestCase;

/**
 * Tests for manager abandoned cart management.
 */
class AbandonedCartTest extends TenantTestCase
{
    private function manager(): User
    {
        return User::create([
            'name' => 'Manager', 'email' => 'mgr@test.com',
            'password' => bcrypt('s'), 'role' => 'manager', 'is_active' => true,
        ]);
    }

    public function test_manager_can_view_abandoned_carts(): void
    {
        $response = $this->actingAs($this->manager(), 'tenant')
            ->withoutTenantMiddleware()
            ->get(route('tenant.manager.abandoned-carts.index'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('Tenant/Manager/AbandonedCarts/Index')
            ->has('carts')
        );
    }

    public function test_abandoned_carts_page_has_stats(): void
    {
        AbandonedCart::create([
            'session_id' => 'sess-' . uniqid(),
            'email' => 'a@test.com',
            'cart_data' => ['items' => [['id' => 1]], 'total' => 50],
        ]);

        $response = $this->actingAs($this->manager(), 'tenant')
            ->withoutTenantMiddleware()
            ->get(route('tenant.manager.abandoned-carts.index'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('Tenant/Manager/AbandonedCarts/Index')
            ->has('carts')
            ->has('stats')
        );
    }

    public function test_manager_can_delete_abandoned_cart(): void
    {
        $cart = AbandonedCart::create([
            'session_id' => 'sess-' . uniqid(),
            'email' => 'a@test.com',
            'cart_data' => ['items' => [['id' => 1]], 'total' => 50],
        ]);

        $response = $this->actingAs($this->manager(), 'tenant')
            ->withoutTenantMiddleware()
            ->deleteJson(route('tenant.manager.abandoned-carts.destroy', $cart));

        $response->assertRedirect();
        $this->assertDatabaseMissing('abandoned_carts', ['id' => $cart->id]);
    }
}
