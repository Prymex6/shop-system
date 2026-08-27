<?php

namespace Tests\Feature\Manager;

use App\Models\Tenant\User;
use Tests\TenantTestCase;

/**
 * Tests for manager role permissions management.
 */
class RolePermissionsTest extends TenantTestCase
{
    private function manager(): User
    {
        return User::create([
            'name' => 'Manager', 'email' => 'mgr@test.com',
            'password' => bcrypt('s'), 'role' => 'manager', 'is_active' => true,
        ]);
    }

    public function test_role_permissions_page_loads(): void
    {
        $response = $this->actingAs($this->manager(), 'tenant')
            ->withoutTenantMiddleware()
            ->get(route('tenant.manager.role-permissions.index'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('Tenant/Manager/RolePermissions')
            ->has('permissions')
        );
    }

    public function test_manager_can_update_role_permissions(): void
    {
        $response = $this->actingAs($this->manager(), 'tenant')
            ->withoutTenantMiddleware()
            ->putJson(route('tenant.manager.role-permissions.update'), [
                'permissions' => [
                    'fulfillment' => ['accept_phone_orders' => true, 'view_cash' => true],
                    'warehouse' => ['accept_phone_orders' => true],
                ],
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('role_permissions', ['role' => 'fulfillment', 'permission' => 'view_cash']);
        $this->assertDatabaseHas('role_permissions', ['role' => 'warehouse', 'permission' => 'accept_phone_orders']);
    }

    public function test_updating_permissions_clears_old_ones(): void
    {
        $manager = $this->manager();

        // First set chef to have 'view_cash'
        $this->actingAs($manager, 'tenant')
            ->withoutTenantMiddleware()
            ->putJson(route('tenant.manager.role-permissions.update'), [
                'permissions' => ['fulfillment' => ['view_cash' => true]],
            ]);

        // Then remove it
        $this->actingAs($manager, 'tenant')
            ->withoutTenantMiddleware()
            ->putJson(route('tenant.manager.role-permissions.update'), [
                'permissions' => ['fulfillment' => []],
            ]);

        $this->assertDatabaseMissing('role_permissions', ['role' => 'fulfillment', 'permission' => 'view_cash']);
    }
}
