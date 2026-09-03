<?php

namespace Tests\Feature;

use App\Models\Tenant\ShippingZone;
use App\Models\Tenant\User;
use Tests\TenantTestCase;

/**
 * Tests for shipping zone management.
 */
class ShippingZoneTest extends TenantTestCase
{
    private function manager(): User
    {
        return User::create([
            'name' => 'Manager', 'email' => 'mgr@test.com',
            'password' => bcrypt('s'), 'role' => 'manager', 'is_active' => true,
        ]);
    }

    public function test_manager_can_view_shipping_zones(): void
    {
        $response = $this->actingAs($this->manager(), 'tenant')
            ->withoutTenantMiddleware()
            ->get(route('tenant.manager.shipping.zones.index'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('Tenant/Manager/Shipping/Zones', false)
            ->has('zones')
        );
    }

    public function test_manager_can_create_shipping_zone(): void
    {
        $response = $this->actingAs($this->manager(), 'tenant')
            ->withoutTenantMiddleware()
            ->postJson(route('tenant.manager.shipping.zones.store'), [
                'name' => 'Europe',
                'countries' => ['PL', 'DE', 'FR'],
                'is_default' => false,
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('shipping_zones', ['name' => 'Europe']);
    }

    public function test_manager_can_update_shipping_zone(): void
    {
        $zone = ShippingZone::create([
            'name' => 'Old Zone',
            'countries' => ['PL'],
            'is_default' => false,
        ]);

        $response = $this->actingAs($this->manager(), 'tenant')
            ->withoutTenantMiddleware()
            ->putJson(route('tenant.manager.shipping.zones.update', $zone), [
                'name' => 'Updated Zone',
                'countries' => ['PL', 'CZ'],
                'is_default' => true,
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('shipping_zones', ['id' => $zone->id, 'name' => 'Updated Zone']);
    }

    public function test_manager_can_delete_shipping_zone(): void
    {
        $zone = ShippingZone::create([
            'name' => 'To Delete',
            'countries' => ['US'],
            'is_default' => false,
        ]);

        $response = $this->actingAs($this->manager(), 'tenant')
            ->withoutTenantMiddleware()
            ->deleteJson(route('tenant.manager.shipping.zones.destroy', $zone));

        $response->assertRedirect();
        $this->assertDatabaseMissing('shipping_zones', ['id' => $zone->id]);
    }

    public function test_shipping_zone_requires_name(): void
    {
        $response = $this->actingAs($this->manager(), 'tenant')
            ->withoutTenantMiddleware()
            ->postJson(route('tenant.manager.shipping.zones.store'), [
                'countries' => ['PL'],
                'is_default' => false,
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['name']);
    }

    public function test_create_zone_with_countries(): void
    {
        $response = $this->actingAs($this->manager(), 'tenant')
            ->withoutTenantMiddleware()
            ->postJson(route('tenant.manager.shipping.zones.store'), [
                'name' => 'Americas',
                'countries' => ['US', 'CA', 'MX', 'BR'],
                'is_default' => false,
            ]);

        $response->assertRedirect();
        $zone = ShippingZone::where('name', 'Americas')->first();
        $this->assertNotNull($zone);
        $this->assertContains('US', $zone->countries);
        $this->assertContains('CA', $zone->countries);
    }
}
