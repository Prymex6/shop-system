<?php

namespace Tests\Feature\Payment;

use App\Http\Middleware\Tenant\CheckSetupComplete;
use App\Models\Tenant\Setting;
use App\Models\Tenant\User;
use Stancl\Tenancy\Middleware\InitializeTenancyByDomain;
use Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains;
use Tests\TenantTestCase;

/**
 * Tests for saving payment gateway settings through the Manager Settings panel.
 * PUT /manager/settings
 */
class PaymentSettingsTest extends TenantTestCase
{
    private function actingAsManager(): static
    {
        $user = User::create([
            'name' => 'Test Manager',
            'email' => 'manager@test.com',
            'password' => bcrypt('password'),
            'role' => 'manager',
        ]);

        return $this->actingAs($user, 'tenant');
    }

    public function test_can_save_p24_settings(): void
    {
        $this->asTenantVersion('test');

        $response = $this->actingAsManager()
            ->withoutTenantMiddleware()
            ->putJson(route('tenant.manager.settings.update'), [
                'payment_p24_enabled' => true,
                'p24_merchant_id' => '999888',
                'p24_pos_id' => '999888',
                'p24_api_key' => 'api-key-secret',
                'p24_crc' => 'crc-secret',
                'p24_sandbox' => true,
            ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('tenant_settings', ['key' => 'p24_merchant_id', 'value' => '999888']);
        // p24_api_key is stored encrypted — verify via Setting::get() which decrypts
        $this->assertEquals('api-key-secret', Setting::get('p24_api_key'));
        $this->assertDatabaseHas('tenant_settings', ['key' => 'payment_p24_enabled', 'value' => '1']);
    }

    public function test_can_save_payu_settings(): void
    {
        $this->asTenantVersion('test');

        $response = $this->actingAsManager()
            ->withoutTenantMiddleware()
            ->putJson(route('tenant.manager.settings.update'), [
                'payment_payu_enabled' => true,
                'payu_pos_id' => '300746',
                'payu_signature_key' => 'md5-key-here',
                'payu_client_id' => 'client-id-here',
                'payu_client_secret' => 'client-secret-here',
                'payu_mode' => 'sandbox',
            ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('tenant_settings', ['key' => 'payu_pos_id', 'value' => '300746']);
        $this->assertDatabaseHas('tenant_settings', ['key' => 'payu_mode', 'value' => 'sandbox']);
        $this->assertDatabaseHas('tenant_settings', ['key' => 'payment_payu_enabled', 'value' => '1']);
    }

    public function test_can_save_tpay_settings(): void
    {
        $this->asTenantVersion('test');

        $response = $this->actingAsManager()
            ->withoutTenantMiddleware()
            ->putJson(route('tenant.manager.settings.update'), [
                'payment_tpay_enabled' => true,
                'tpay_client_id' => 'tpay-client-123',
                'tpay_client_secret' => 'tpay-secret-456',
                'tpay_mode' => 'production',
            ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('tenant_settings', ['key' => 'tpay_client_id', 'value' => 'tpay-client-123']);
        $this->assertDatabaseHas('tenant_settings', ['key' => 'tpay_mode', 'value' => 'production']);
    }

    public function test_can_save_tpay_notification_secret(): void
    {
        $this->asTenantVersion('test');

        $response = $this->actingAsManager()
            ->withoutTenantMiddleware()
            ->putJson(route('tenant.manager.settings.update'), [
                'payment_tpay_enabled' => true,
                'tpay_client_id' => 'client-123',
                'tpay_client_secret' => 'secret-456',
                'tpay_notification_secret' => 'notif-secret-789',
            ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('tenant_settings', ['key' => 'tpay_client_id', 'value' => 'client-123']);
        $this->assertDatabaseHas('tenant_settings', ['key' => 'payment_tpay_enabled', 'value' => '1']);
    }

    public function test_can_disable_payment_gateway(): void
    {
        $this->asTenantVersion('test');
        $this->setSetting('payment_tpay_enabled', '1');
        $this->setSetting('tpay_client_secret', 'secret-existing');

        $this->actingAsManager()
            ->withoutTenantMiddleware()
            ->putJson(route('tenant.manager.settings.update'), [
                'payment_tpay_enabled' => false,
                'tpay_client_secret' => 'secret-existing',
            ]);

        $this->assertDatabaseHas('tenant_settings', [
            'key' => 'payment_tpay_enabled',
            'value' => '0',
        ]);
    }

    public function test_stable_version_cannot_save_online_gateway_settings(): void
    {
        $this->asTenantVersion('stable');

        $this->actingAsManager()
            ->withoutTenantMiddleware()
            ->putJson(route('tenant.manager.settings.update'), [
                'payment_tpay_enabled' => true,
                'tpay_client_secret' => 'secret-blocked',
            ])
            ->assertRedirect();

        // Setting should NOT be saved for stable version
        $this->assertDatabaseMissing('tenant_settings', ['key' => 'tpay_client_secret']);
        $this->assertDatabaseMissing('tenant_settings', ['key' => 'payment_tpay_enabled', 'value' => '1']);
    }

    public function test_tpay_mode_validation_rejects_invalid_value(): void
    {
        $response = $this->actingAsManager()
            ->withoutTenantMiddleware()
            ->putJson(route('tenant.manager.settings.update'), [
                'tpay_mode' => 'invalid-mode',
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['tpay_mode']);
    }

    public function test_payu_mode_validation_rejects_invalid_value(): void
    {
        $response = $this->actingAsManager()
            ->withoutTenantMiddleware()
            ->putJson(route('tenant.manager.settings.update'), [
                'payu_mode' => 'test',
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['payu_mode']);
    }

    public function test_unauthenticated_cannot_save_settings(): void
    {
        // Only bypass tenancy domain middleware, NOT EnsureTenantAuth
        $response = $this->withoutMiddleware([
            InitializeTenancyByDomain::class,
            PreventAccessFromCentralDomains::class,
            CheckSetupComplete::class,
        ])
            ->putJson(route('tenant.manager.settings.update'), [
                'tpay_client_secret' => 'secret-hacked',
            ]);

        // EnsureTenantAuth redirects unauthenticated users to login (302)
        $response->assertStatus(302);
        $this->assertDatabaseMissing('tenant_settings', ['key' => 'tpay_client_secret']);
    }
}
