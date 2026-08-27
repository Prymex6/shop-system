<?php

namespace Tests\Feature;

use App\Models\Tenant\Setting;
use App\Models\Tenant\User;
use Illuminate\Support\Facades\DB;
use Tests\TenantTestCase;

/**
 * Step 1 of the install wizard used to accept an empty shop_address —
 * the seeded legal pages (Regulamin, Polityka Prywatności) substitute it
 * into a {shop_address} token, so a shop that only filled shop_name
 * published a Regulamin missing the seller's address, required by art. 12
 * of the Polish consumer rights act. shop_address is now required here.
 */
class InstallWizardTest extends TenantTestCase
{
    private function manager(): User
    {
        return User::create([
            'name' => 'Owner', 'email' => 'owner@test.com',
            'password' => bcrypt('secret'), 'role' => 'manager', 'is_active' => true,
        ]);
    }

    protected function setUp(): void
    {
        parent::setUp();
        // TenantTestCase seeds setup_completed=1 for every other test suite —
        // the install wizard's own "check.setup.pending" middleware blocks the
        // whole route group once that's true, so these tests need it undone.
        DB::table('tenant_settings')->where('key', 'setup_completed')->delete();
        Setting::flushRuntimeCache();
    }

    public function test_shop_step_requires_address(): void
    {
        $response = $this->actingAs($this->manager(), 'tenant')
            ->withoutTenantMiddleware()
            ->postJson(route('tenant.install.shop'), [
                'shop_name' => 'Mój Sklep',
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['shop_address']);
    }

    public function test_shop_step_saves_address_and_completes_setup(): void
    {
        $response = $this->actingAs($this->manager(), 'tenant')
            ->withoutTenantMiddleware()
            ->post(route('tenant.install.shop'), [
                'shop_name' => 'Mój Sklep',
                'shop_address' => 'ul. Testowa 1, 00-001 Warszawa',
                'shop_nip' => '1234567890',
            ]);

        $response->assertRedirect();
        $this->assertEquals('ul. Testowa 1, 00-001 Warszawa', Setting::get('shop_address'));
        $this->assertEquals('1234567890', Setting::get('shop_nip'));
        $this->assertTrue((bool) Setting::get('setup_completed'));
    }
}
