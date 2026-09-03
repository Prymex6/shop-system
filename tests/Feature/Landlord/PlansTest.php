<?php

namespace Tests\Feature\Landlord;

use App\Models\Landlord\Plan;
use Tests\LandlordTestCase;

/**
 * Tests for super-admin plans management (CRUD in the landlord panel).
 */
class PlansTest extends LandlordTestCase
{
    public function test_admin_can_view_plans_list(): void
    {
        Plan::create(['name' => 'Basic', 'slug' => 'basic', 'price' => 480, 'is_active' => true]);

        $response = $this->actingAs($this->superAdmin(), 'super_admin')
            ->get(route('landlord.plans.index'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('Landlord/Plans/Index')
            ->has('plans')
        );
    }

    public function test_admin_can_view_create_plan_form(): void
    {
        $response = $this->actingAs($this->superAdmin(), 'super_admin')
            ->get(route('landlord.plans.create'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('Landlord/Plans/Form')
        );
    }

    public function test_admin_can_create_plan(): void
    {
        $response = $this->actingAs($this->superAdmin(), 'super_admin')
            ->postJson(route('landlord.plans.store'), [
                'name' => 'Standard',
                'price' => 480.00,
                'max_orders_per_month' => 1000,
                'features' => ['Nieograniczone zamówienia', 'Wsparcie 24/7'],
                'is_active' => true,
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('plans', ['name' => 'Standard', 'price' => 480.00], 'central');
    }

    public function test_admin_can_set_product_staff_and_storage_limits(): void
    {
        $response = $this->actingAs($this->superAdmin(), 'super_admin')
            ->postJson(route('landlord.plans.store'), [
                'name' => 'Pro',
                'price' => 990,
                'max_products' => 500,
                'max_staff' => 10,
                'max_storage_mb' => 2048,
                'is_active' => true,
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('plans', [
            'name' => 'Pro', 'max_products' => 500, 'max_staff' => 10, 'max_storage_mb' => 2048,
        ], 'central');
    }

    public function test_create_plan_requires_name(): void
    {
        $response = $this->actingAs($this->superAdmin(), 'super_admin')
            ->postJson(route('landlord.plans.store'), [
                'price' => 100,
                'is_active' => true,
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['name']);
    }

    public function test_create_plan_price_must_be_numeric(): void
    {
        $response = $this->actingAs($this->superAdmin(), 'super_admin')
            ->postJson(route('landlord.plans.store'), [
                'name' => 'Plan',
                'price' => 'darmowy',
                'is_active' => true,
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['price']);
    }

    public function test_admin_can_view_edit_plan_form(): void
    {
        $plan = Plan::create(['name' => 'Edit Me', 'slug' => 'edit-me', 'price' => 100, 'is_active' => true]);

        $response = $this->actingAs($this->superAdmin(), 'super_admin')
            ->get(route('landlord.plans.edit', $plan));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('Landlord/Plans/Form')
            ->has('plan')
        );
    }

    public function test_admin_can_update_plan(): void
    {
        $plan = Plan::create(['name' => 'Old Name', 'slug' => 'old-name', 'price' => 100, 'is_active' => true]);

        $response = $this->actingAs($this->superAdmin(), 'super_admin')
            ->put(route('landlord.plans.update', $plan), [
                'name' => 'New Name',
                'price' => 200.00,
                'is_active' => true,
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('plans', ['id' => $plan->id, 'name' => 'New Name', 'price' => 200.00], 'central');
    }

    public function test_admin_can_delete_plan_without_tenants(): void
    {
        $plan = Plan::create(['name' => 'Delete Me', 'slug' => 'delete-me', 'price' => 0, 'is_active' => true]);

        $response = $this->actingAs($this->superAdmin(), 'super_admin')
            ->deleteJson(route('landlord.plans.destroy', $plan));

        $response->assertRedirect();
        $this->assertDatabaseMissing('plans', ['id' => $plan->id], 'central');
    }

    public function test_plan_price_is_optional_defaults_to_zero(): void
    {
        // plans.price column is NOT NULL with default 0 — omitting price should succeed.
        $response = $this->actingAs($this->superAdmin(), 'super_admin')
            ->postJson(route('landlord.plans.store'), [
                'name' => 'Free',
                'is_active' => true,
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('plans', ['name' => 'Free', 'price' => 0], 'central');
    }

    public function test_unauthenticated_admin_is_redirected(): void
    {
        $response = $this->get(route('landlord.plans.index'));

        $response->assertRedirect();
    }
}
