<?php

namespace Tests\Feature;

use App\Models\Tenant\Product;
use App\Models\Tenant\Setting;
use App\Models\Tenant\ShippingMethod;
use App\Models\Tenant\ShippingZone;
use App\Models\Tenant\User;
use App\Services\ShippingCalculatorService;
use Tests\TenantTestCase;

/**
 * Round 23 finding: Settings/Index.vue has had a "Darmowa dostawa od (PLN)"
 * field (free_shipping_threshold) since before this test existed, but
 * SettingsController::update() never whitelisted it for saving (silently
 * dropped on every submit) and nothing server-side ever read it even if it
 * had been saved — a merchant could type a number into that field forever
 * and it would never grant free shipping to anyone.
 */
class ShippingCalculatorServiceTest extends TenantTestCase
{
    private function service(): ShippingCalculatorService
    {
        return app(ShippingCalculatorService::class);
    }

    private function defaultZoneWithMethod(float $price = 20.0): array
    {
        $zone = ShippingZone::create(['name' => 'PL', 'countries' => ['PL'], 'is_default' => true]);
        $method = ShippingMethod::create([
            'name' => 'Kurier', 'type' => 'standard', 'price' => $price,
            'delivery_days_min' => 1, 'delivery_days_max' => 2, 'is_active' => true,
        ]);
        $zone->methods()->attach($method->id);

        return [$zone, $method];
    }

    public function test_global_threshold_disabled_by_default_does_not_affect_cost(): void
    {
        [, $method] = $this->defaultZoneWithMethod(20.0);

        $cost = $this->service()->calculateCost($method, 'PL', 500.0);

        $this->assertEquals(20.0, $cost);
    }

    public function test_global_threshold_grants_free_shipping_once_reached(): void
    {
        Setting::set('free_shipping_threshold', 200, 'string');
        [, $method] = $this->defaultZoneWithMethod(20.0);

        $belowThreshold = $this->service()->calculateCost($method, 'PL', 150.0);
        $atThreshold = $this->service()->calculateCost($method, 'PL', 200.0);

        $this->assertEquals(20.0, $belowThreshold);
        $this->assertEquals(0.0, $atThreshold);
    }

    public function test_get_available_methods_reflects_global_threshold(): void
    {
        Setting::set('free_shipping_threshold', 200, 'string');
        $this->defaultZoneWithMethod(20.0);

        $methods = $this->service()->getAvailableMethods('PL', 250.0);

        $this->assertEquals(0.0, $methods[0]['effective_price']);
    }

    public function test_zero_threshold_means_disabled(): void
    {
        Setting::set('free_shipping_threshold', 0, 'string');
        [, $method] = $this->defaultZoneWithMethod(20.0);

        $cost = $this->service()->calculateCost($method, 'PL', 999999.0);

        $this->assertEquals(20.0, $cost);
    }

    /**
     * SettingsController::update() validated an explicit whitelist of keys
     * and silently dropped anything not in it — free_shipping_threshold and
     * order_bump_product_id were both missing from that whitelist (and from
     * $settingsSchema, checked again before the actual Setting::set() call),
     * so submitting the form field that's existed in Settings/Index.vue this
     * whole time never persisted anything at all.
     */
    public function test_manager_can_save_free_shipping_threshold_and_order_bump_product(): void
    {
        $manager = User::create([
            'name' => 'Manager', 'email' => 'mgr-ship@test.com',
            'password' => bcrypt('s'), 'role' => 'manager', 'is_active' => true,
        ]);
        $product = Product::create([
            'name' => 'Gratis Sample', 'slug' => 'gratis-sample-' . uniqid(),
            'sku' => 'BUMP-' . uniqid(), 'price' => 4.99, 'is_active' => true,
            'is_published' => true, 'track_stock' => false,
        ]);

        $response = $this->actingAs($manager, 'tenant')
            ->withoutTenantMiddleware()
            ->put(route('tenant.manager.settings.update'), [
                'free_shipping_threshold' => 199.99,
                'order_bump_product_id' => $product->id,
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('tenant_settings', ['key' => 'free_shipping_threshold', 'value' => '199.99']);
        $this->assertDatabaseHas('tenant_settings', ['key' => 'order_bump_product_id', 'value' => (string) $product->id]);
    }
}
