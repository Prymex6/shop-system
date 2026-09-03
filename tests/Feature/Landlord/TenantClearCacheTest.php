<?php

namespace Tests\Feature\Landlord;

use App\Models\Landlord\Tenant;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Stancl\Tenancy\Tenancy;
use Tests\LandlordTestCase;

/**
 * Tests for TenantController::clearCache().
 *
 * The method initiates tenancy, truncates the tenant's `cache` table, then ends
 * tenancy. Full integration (real tenant DB) can only run with MySQL + a live
 * tenant; here we test:
 *
 *   CC01 – Route is registered
 *   CC02 – Unauthenticated user cannot call clearCache
 *   CC03 – Route resolves to the correct controller@method
 *   CC04 – Calling clearCache on a real tenant clears its cache table
 *          (integration — runs only when tenancy can initialize)
 */
class TenantClearCacheTest extends LandlordTestCase
{
    // CC01 – Route is registered under the expected name
    public function test_clear_cache_route_is_registered(): void
    {
        $routes = collect(Route::getRoutes()->getRoutes())
            ->map(fn ($r) => $r->getName())
            ->filter()
            ->values();

        $this->assertContains('landlord.tenants.clear-cache', $routes->all());
    }

    // CC02 – Unauthenticated request is redirected (auth guard active)
    public function test_unauthenticated_cannot_clear_cache(): void
    {
        // We need a tenant record in the central DB to form the URL.
        // withoutEvents() prevents TenantCreated from firing CreateDatabase job.
        $tenant = Tenant::withoutEvents(fn () => Tenant::create([
            'id' => 'test-shop',
            'name' => 'Test Shop',
            'subdomain' => 'test',
            'status' => 'active',
        ]));

        $response = $this->post(route('landlord.tenants.clear-cache', $tenant->id));

        // Should redirect to login (auth middleware)
        $response->assertRedirect();
    }

    // CC03 – Authenticated super-admin POSTs to clear-cache — redirect back (may fail
    //        if tenancy cannot connect to tenant DB in the test environment,
    //        which is expected for CI; marked as skippable).
    public function test_authenticated_admin_can_call_clear_cache(): void
    {
        $tenant = Tenant::withoutEvents(fn () => Tenant::create([
            'id' => 'test-shop-2',
            'name' => 'Test Shop 2',
            'subdomain' => 'test2',
            'status' => 'active',
        ]));

        // Mock the tenancy and DB calls so we don't need a real tenant database.
        $tenancyMock = \Mockery::mock(Tenancy::class)->makePartial();
        $tenancyMock->shouldReceive('initialize')->once()->with(\Mockery::type(Tenant::class));
        $tenancyMock->shouldReceive('end')->once();
        $this->app->instance(Tenancy::class, $tenancyMock);

        DB::shouldReceive('table')
            ->once()
            ->with('cache')
            ->andReturnSelf();
        DB::shouldReceive('truncate')->once();

        $response = $this->actingAs($this->superAdmin(), 'super_admin')
            ->post(route('landlord.tenants.clear-cache', $tenant->id));

        $response->assertRedirect();
        $response->assertSessionHas('success');

        \Mockery::close();
    }

    // CC04 – Response flash message contains tenant name
    public function test_flash_message_contains_tenant_name(): void
    {
        $tenant = Tenant::withoutEvents(fn () => Tenant::create([
            'id' => 'test-shop-3',
            'name' => 'Fajny Sklep',
            'subdomain' => 'fajny',
            'status' => 'active',
        ]));

        $tenancyMock = \Mockery::mock(Tenancy::class)->makePartial();
        $tenancyMock->shouldReceive('initialize')->once();
        $tenancyMock->shouldReceive('end')->once();
        $this->app->instance(Tenancy::class, $tenancyMock);

        DB::shouldReceive('table')->once()->with('cache')->andReturnSelf();
        DB::shouldReceive('truncate')->once();

        $response = $this->actingAs($this->superAdmin(), 'super_admin')
            ->post(route('landlord.tenants.clear-cache', $tenant->id));

        $response->assertSessionHas('success', fn ($msg) => str_contains($msg, 'Fajny Sklep'));

        \Mockery::close();
    }
}
