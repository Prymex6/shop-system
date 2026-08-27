<?php

namespace Tests\Feature\Manager;

use App\Models\Tenant\DiscountCode;
use App\Models\Tenant\User;
use Tests\TenantTestCase;

/**
 * Tests for discount code CRUD management.
 */
class DiscountCodeTest extends TenantTestCase
{
    private function manager(): User
    {
        return User::create([
            'name' => 'Manager', 'email' => 'mgr@test.com',
            'password' => bcrypt('s'), 'role' => 'manager', 'is_active' => true,
        ]);
    }

    public function test_manager_can_view_discounts_list(): void
    {
        $response = $this->actingAs($this->manager(), 'tenant')
            ->withoutTenantMiddleware()
            ->get(route('tenant.manager.discounts.index'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('Tenant/Manager/Discounts/Index')
        );
    }

    public function test_manager_can_create_percentage_discount(): void
    {
        $response = $this->actingAs($this->manager(), 'tenant')
            ->withoutTenantMiddleware()
            ->postJson(route('tenant.manager.discounts.store'), [
                'code' => 'LATO20',
                'type' => 'percentage',
                'value' => 20,
                'is_active' => true,
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('discount_codes', ['code' => 'LATO20', 'type' => 'percentage', 'value' => 20]);
    }

    public function test_manager_can_create_fixed_discount(): void
    {
        $response = $this->actingAs($this->manager(), 'tenant')
            ->withoutTenantMiddleware()
            ->postJson(route('tenant.manager.discounts.store'), [
                'code' => 'RABAT5',
                'type' => 'fixed',
                'value' => 5,
                'min_order_value' => 30,
                'is_active' => true,
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('discount_codes', ['code' => 'RABAT5', 'type' => 'fixed']);
    }

    public function test_discount_code_must_be_unique(): void
    {
        DiscountCode::create(['code' => 'UNIQ', 'type' => 'fixed', 'value' => 5, 'is_active' => true]);

        $response = $this->actingAs($this->manager(), 'tenant')
            ->withoutTenantMiddleware()
            ->postJson(route('tenant.manager.discounts.store'), [
                'code' => 'UNIQ',
                'type' => 'fixed',
                'value' => 10,
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['code']);
    }

    public function test_manager_can_update_discount(): void
    {
        $discount = DiscountCode::create(['code' => 'OLDCODE', 'type' => 'fixed', 'value' => 5, 'is_active' => true]);

        $response = $this->actingAs($this->manager(), 'tenant')
            ->withoutTenantMiddleware()
            ->putJson(route('tenant.manager.discounts.update', $discount), [
                'code' => 'NEWCODE',
                'type' => 'fixed',
                'value' => 10,
                'is_active' => true,
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('discount_codes', ['id' => $discount->id, 'code' => 'NEWCODE', 'value' => 10]);
    }

    public function test_manager_can_delete_discount(): void
    {
        $discount = DiscountCode::create(['code' => 'DEL', 'type' => 'fixed', 'value' => 5, 'is_active' => true]);

        $response = $this->actingAs($this->manager(), 'tenant')
            ->withoutTenantMiddleware()
            ->deleteJson(route('tenant.manager.discounts.destroy', $discount));

        $response->assertRedirect();
        $this->assertDatabaseMissing('discount_codes', ['id' => $discount->id]);
    }

    public function test_manager_can_toggle_discount_status(): void
    {
        $discount = DiscountCode::create(['code' => 'TOG', 'type' => 'fixed', 'value' => 5, 'is_active' => true]);

        $response = $this->actingAs($this->manager(), 'tenant')
            ->withoutTenantMiddleware()
            ->postJson(route('tenant.manager.discounts.toggle', $discount));

        $response->assertRedirect();
        $discount->refresh();
        $this->assertFalse((bool) $discount->is_active);
    }

    public function test_discount_code_must_be_at_least_6_characters(): void
    {
        $response = $this->actingAs($this->manager(), 'tenant')
            ->withoutTenantMiddleware()
            ->postJson(route('tenant.manager.discounts.store'), [
                'code' => 'VIP',
                'type' => 'fixed',
                'value' => 10,
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['code']);
    }

    public function test_discount_type_must_be_valid(): void
    {
        $response = $this->actingAs($this->manager(), 'tenant')
            ->withoutTenantMiddleware()
            ->postJson(route('tenant.manager.discounts.store'), [
                'code' => 'BADTYPE',
                'type' => 'invalid',
                'value' => 10,
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['type']);
    }
}
