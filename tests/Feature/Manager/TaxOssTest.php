<?php

namespace Tests\Feature\Manager;

use App\Models\Tenant\TaxRate;
use App\Models\Tenant\User;
use Tests\TenantTestCase;

/**
 * TaxRate::$fillable omitted country_code/is_eu_oss/eu_vat_rate (added by
 * the extend_tax_rates_for_eu_oss migration) — the manager "Add OSS rate"
 * form's updateOrCreate() call silently discarded eu_vat_rate/is_eu_oss on
 * the fill() half of the call, so the saved row never actually carried the
 * EU VAT rate a manager typed in.
 */
class TaxOssTest extends TenantTestCase
{
    private function manager(): User
    {
        return User::create([
            'name' => 'Manager', 'email' => 'mgr@test.com',
            'password' => bcrypt('s'), 'role' => 'manager', 'is_active' => true,
        ]);
    }

    public function test_manager_can_save_eu_oss_tax_rate_with_all_fields(): void
    {
        $response = $this->actingAs($this->manager(), 'tenant')
            ->withoutTenantMiddleware()
            ->post(route('tenant.manager.tax.oss.store'), [
                'country_code' => 'DE',
                'eu_vat_rate' => 19.00,
                'name' => 'Niemcy OSS',
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('tax_rates', [
            'country_code' => 'DE',
            'is_eu_oss' => 1,
            'eu_vat_rate' => 19.00,
            'rate' => 19.00,
            'name' => 'Niemcy OSS',
        ]);
    }

    public function test_updating_existing_oss_rate_persists_new_vat_rate(): void
    {
        TaxRate::create([
            'name' => 'Niemcy OSS', 'rate' => 19, 'country_code' => 'DE',
            'is_eu_oss' => true, 'eu_vat_rate' => 19, 'is_active' => true,
        ]);

        $this->actingAs($this->manager(), 'tenant')
            ->withoutTenantMiddleware()
            ->post(route('tenant.manager.tax.oss.store'), [
                'country_code' => 'DE',
                'eu_vat_rate' => 21.00,
            ]);

        $this->assertDatabaseHas('tax_rates', [
            'country_code' => 'DE', 'eu_vat_rate' => 21.00, 'rate' => 21.00,
        ]);
        $this->assertEquals(1, TaxRate::where('country_code', 'DE')->count());
    }
}
