<?php

namespace Tests\Feature\Landlord;

use App\Http\Controllers\LandingController;
use App\Models\Landlord\Plan;
use Illuminate\Support\Facades\Route;
use Tests\LandlordTestCase;

/**
 * Tests for the public landing page (central domain root route).
 *
 * NOTE: The named route 'landing' is overridden by tenant routes (tenant.menu)
 * that are loaded after web routes. We re-register it at a unique path in setUp()
 * and hit that URL directly to avoid the naming conflict.
 */
class LandingTest extends LandlordTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Re-register the landing controller at a collision-free path.
        // We cannot use route('landing') because TenancyServiceProvider loads
        // tenant routes after web routes and the 'landing' name disappears.
        Route::get('/_test/landing', [LandingController::class, 'index'])
            ->middleware('web');
    }

    public function test_landing_page_loads(): void
    {
        $response = $this->get('/_test/landing');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('Landing')
            ->has('plans')
        );
    }

    public function test_landing_page_shows_active_plans(): void
    {
        Plan::on('central')->create([
            'name' => 'Basic',
            'slug' => 'basic',
            'price' => 480.00,
            'is_active' => true,
        ]);
        Plan::on('central')->create([
            'name' => 'Hidden',
            'slug' => 'hidden',
            'price' => 0,
            'is_active' => false,
        ]);

        $response = $this->get('/_test/landing');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('Landing')
            ->where('plans.0.name', 'Basic')
            ->count('plans', 1) // only active plans
        );
    }

    public function test_landing_page_loads_without_plans(): void
    {
        $response = $this->get('/_test/landing');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('Landing')
            ->count('plans', 0)
        );
    }
}
