<?php

namespace Tests\Feature\Landlord;

use App\Models\Landlord\Tenant;
use Tests\LandlordTestCase;

/**
 * Tests for TenantController::show() — resources/js/Pages/Landlord/Tenants/Show.vue
 * did not exist on disk before this fix, causing a hard Inertia crash for any
 * super-admin visiting /admin/tenants/{id} directly.
 */
class TenantShowTest extends LandlordTestCase
{
    public function test_unauthenticated_cannot_view_tenant_show(): void
    {
        $tenant = Tenant::withoutEvents(fn () => Tenant::create([
            'id' => 'show-test-1',
            'name' => 'Show Test Shop',
            'subdomain' => 'showtest1',
            'status' => 'active',
        ]));

        $response = $this->get(route('landlord.tenants.show', $tenant->id));

        $response->assertRedirect();
    }

    public function test_super_admin_can_view_tenant_show(): void
    {
        $tenant = Tenant::withoutEvents(fn () => Tenant::create([
            'id' => 'show-test-2',
            'name' => 'Show Test Shop 2',
            'subdomain' => 'showtest2',
            'status' => 'active',
        ]));

        $response = $this->actingAs($this->superAdmin(), 'super_admin')
            ->get(route('landlord.tenants.show', $tenant->id));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page->component('Landlord/Tenants/Show')
            ->where('tenant.id', 'show-test-2')
        );
    }
}
