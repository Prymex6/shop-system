<?php

namespace Tests\Feature;

use App\Models\Tenant\User;
use Illuminate\Support\Facades\DB;
use Tests\TenantTestCase;

/**
 * Tests for the initial setup wizard (3-step configuration).
 */
class SetupWizardTest extends TenantTestCase
{
    private function manager(): User
    {
        return User::create([
            'name' => 'Manager', 'email' => 'mgr@test.com',
            'password' => bcrypt('s'), 'role' => 'manager', 'is_active' => true,
        ]);
    }

    public function test_setup_wizard_page_loads(): void
    {
        // Clear setup_completed so the wizard page is accessible (not redirected to dashboard)
        DB::table('tenant_settings')
            ->where('key', 'setup_completed')->delete();

        $response = $this->actingAs($this->manager(), 'tenant')
            ->withoutTenantMiddleware()
            ->get(route('tenant.manager.setup'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('Tenant/Manager/Setup')
        );
    }

    public function test_step1_saves_shop_info(): void
    {
        $response = $this->actingAs($this->manager(), 'tenant')
            ->withoutTenantMiddleware()
            ->postJson(route('tenant.manager.setup.store'), [
                'step' => 'shop',
                'shop_name' => 'Mój Sklep',
                'shop_phone' => '123456789',
                'shop_email' => 'kontakt@sklep.pl',
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('tenant_settings', ['key' => 'shop_name', 'value' => 'Mój Sklep']);
    }

    public function test_step2_saves_opening_hours(): void
    {
        $hours = [
            'monday' => ['closed' => false, 'open' => '10:00', 'close' => '22:00'],
            'tuesday' => ['closed' => false, 'open' => '10:00', 'close' => '22:00'],
            'wednesday' => ['closed' => false, 'open' => '10:00', 'close' => '22:00'],
            'thursday' => ['closed' => false, 'open' => '10:00', 'close' => '22:00'],
            'friday' => ['closed' => false, 'open' => '10:00', 'close' => '23:00'],
            'saturday' => ['closed' => false, 'open' => '11:00', 'close' => '23:00'],
            'sunday' => ['closed' => true,  'open' => '11:00', 'close' => '22:00'],
        ];

        $response = $this->actingAs($this->manager(), 'tenant')
            ->withoutTenantMiddleware()
            ->postJson(route('tenant.manager.setup.store'), [
                'step' => 'hours',
                'opening_hours' => $hours,
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('tenant_settings', ['key' => 'opening_hours']);
    }

    public function test_step3_marks_setup_as_complete(): void
    {
        $response = $this->actingAs($this->manager(), 'tenant')
            ->withoutTenantMiddleware()
            ->postJson(route('tenant.manager.setup.store'), [
                'step' => 'complete',
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('tenant_settings', ['key' => 'setup_completed', 'value' => '1']);
    }

    public function test_step1_requires_restaurant_name(): void
    {
        $response = $this->actingAs($this->manager(), 'tenant')
            ->withoutTenantMiddleware()
            ->postJson(route('tenant.manager.setup.store'), [
                'step' => 'shop',
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['shop_name']);
    }
}
