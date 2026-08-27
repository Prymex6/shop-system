<?php

namespace Tests\Feature\Manager;

use App\Models\Tenant\Customer;
use App\Models\Tenant\User;
use Tests\TenantTestCase;

/**
 * Tests for the manager-side GDPR erasure request panel.
 */
class GdprTest extends TenantTestCase
{
    private function manager(): User
    {
        return User::create([
            'name' => 'Manager', 'email' => 'mgr@test.com',
            'password' => bcrypt('s'), 'role' => 'manager', 'is_active' => true,
        ]);
    }

    public function test_manager_can_view_pending_gdpr_requests(): void
    {
        Customer::create([
            'name' => 'Pending Delete', 'email' => 'pending@test.com', 'password' => bcrypt('s'),
            'delete_requested_at' => now(),
        ]);

        $response = $this->actingAs($this->manager(), 'tenant')
            ->withoutTenantMiddleware()
            ->get(route('tenant.manager.gdpr.requests'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('Tenant/Manager/Gdpr/Index')
            ->has('customers', 1)
        );
    }

    public function test_manager_can_anonymize_customer(): void
    {
        $customer = Customer::create([
            'name' => 'Real Name', 'email' => 'real@test.com', 'password' => bcrypt('s'),
            'phone' => '123456789', 'delete_requested_at' => now(),
        ]);

        // This route/method didn't exist at all before — the only button on
        // this page threw route-not-found, so staff could never actually
        // fulfil an erasure request from the admin panel.
        $response = $this->actingAs($this->manager(), 'tenant')
            ->withoutTenantMiddleware()
            ->post(route('tenant.manager.gdpr.anonymize', $customer));

        $response->assertRedirect();
        $customer->refresh();
        $this->assertNotEquals('Real Name', $customer->name);
        $this->assertNotEquals('real@test.com', $customer->email);
        $this->assertNull($customer->phone);
    }
}
