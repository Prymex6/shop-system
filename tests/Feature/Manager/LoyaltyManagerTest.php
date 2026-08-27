<?php

namespace Tests\Feature\Manager;

use App\Models\Tenant\Customer;
use App\Models\Tenant\User;
use Tests\TenantTestCase;

/**
 * Tests for manager loyalty program management.
 */
class LoyaltyManagerTest extends TenantTestCase
{
    private function manager(): User
    {
        return User::create([
            'name' => 'Manager', 'email' => 'mgr@test.com',
            'password' => bcrypt('s'), 'role' => 'manager', 'is_active' => true,
        ]);
    }

    private function customer(int $points = 0): Customer
    {
        return Customer::create([
            'name' => 'Klient',
            'email' => 'klient@test.com',
            'phone' => '123456789',
            'loyalty_points' => $points,
            'loyalty_tier' => 'bronze',
        ]);
    }

    public function test_loyalty_page_loads(): void
    {
        $response = $this->actingAs($this->manager(), 'tenant')
            ->withoutTenantMiddleware()
            ->get(route('tenant.manager.loyalty.index'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('Tenant/Manager/Loyalty/Index')
            ->has('customers')
        );
    }

    public function test_manager_can_add_loyalty_points(): void
    {
        $manager = $this->manager();
        $customer = $this->customer(100);

        $response = $this->actingAs($manager, 'tenant')
            ->withoutTenantMiddleware()
            ->postJson(route('tenant.manager.loyalty.add-points', $customer), [
                'points' => 50,
                'description' => 'Bonus punktów',
            ]);

        $response->assertRedirect();
        $customer->refresh();
        $this->assertEquals(150, $customer->loyalty_points);
    }

    public function test_manager_can_deduct_loyalty_points(): void
    {
        $manager = $this->manager();
        $customer = $this->customer(200);

        $response = $this->actingAs($manager, 'tenant')
            ->withoutTenantMiddleware()
            ->postJson(route('tenant.manager.loyalty.add-points', $customer), [
                'points' => -50,
                'description' => 'Korekta',
            ]);

        $response->assertRedirect();
        $customer->refresh();
        $this->assertEquals(150, $customer->loyalty_points);
    }

    public function test_loyalty_point_adjustment_requires_points(): void
    {
        $manager = $this->manager();
        $customer = $this->customer();

        $response = $this->actingAs($manager, 'tenant')
            ->withoutTenantMiddleware()
            ->postJson(route('tenant.manager.loyalty.add-points', $customer), [
                'description' => 'Missing points field',
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['points']);
    }
}
