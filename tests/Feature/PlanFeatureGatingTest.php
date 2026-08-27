<?php

namespace Tests\Feature;

use App\Models\Landlord\Plan;
use App\Models\Tenant\Customer;
use App\Models\Tenant\EmailCampaign;
use App\Models\Tenant\Product;
use App\Models\Tenant\Setting;
use App\Models\Tenant\User;
use App\Models\Tenant\Warehouse;
use App\Services\LoyaltyService;
use Illuminate\Support\Facades\Mail;
use Tests\TenantTestCase;

/**
 * Plan::features (LandlordSeeder — online_payments, digital_products,
 * loyalty_program, sms_notifications, email_campaigns, custom_css,
 * analytics, multi_warehouse, abandoned_cart, thermal_printer) was a JSON
 * flag list only ever written by the landlord Plans form and never once
 * read by any tenant-side code — every tenant had every feature regardless
 * of what their plan actually included. Faking tenancy()->tenant mirrors
 * PlanResourceLimitTest's established pattern, using a real Plan model (not
 * a stdClass) since hasFeature() is a method, not a plain property.
 */
class PlanFeatureGatingTest extends TenantTestCase
{
    private function fakeTenantWithFeatures(array $features): void
    {
        $plan = new Plan(['features' => $features]);

        $fakeTenant = new class($plan)
        {
            public function __construct(public $plan) {}

            public function getAttribute($key)
            {
                return $key === 'id' ? 'test-tenant-id' : null;
            }
        };

        tenancy()->tenant = $fakeTenant;
    }

    private function manager(): User
    {
        return User::create([
            'name' => 'Manager', 'email' => 'manager@test.com',
            'password' => bcrypt('secret'), 'role' => 'manager', 'is_active' => true,
        ]);
    }

    // ─── digital_products ──────────────────────────────────────────────

    public function test_digital_product_creation_blocked_without_feature(): void
    {
        $this->fakeTenantWithFeatures(['digital_products' => false]);

        $response = $this->actingAs($this->manager(), 'tenant')
            ->withoutTenantMiddleware()
            ->post(route('tenant.manager.products.store'), [
                'name' => 'E-book', 'type' => 'digital', 'price' => 20,
            ]);

        $response->assertSessionHasErrors();
        $this->assertEquals(0, Product::count());
    }

    public function test_physical_product_creation_unaffected_by_digital_products_feature(): void
    {
        $this->fakeTenantWithFeatures(['digital_products' => false]);

        $this->actingAs($this->manager(), 'tenant')
            ->withoutTenantMiddleware()
            ->post(route('tenant.manager.products.store'), [
                'name' => 'Koszulka', 'type' => 'physical', 'price' => 20,
            ])->assertSessionHasNoErrors();

        $this->assertEquals(1, Product::count());
    }

    public function test_digital_product_creation_allowed_with_feature(): void
    {
        $this->fakeTenantWithFeatures(['digital_products' => true]);

        $this->actingAs($this->manager(), 'tenant')
            ->withoutTenantMiddleware()
            ->post(route('tenant.manager.products.store'), [
                'name' => 'E-book', 'type' => 'digital', 'price' => 20,
            ])->assertSessionHasNoErrors();

        $this->assertEquals(1, Product::count());
    }

    // ─── multi_warehouse ────────────────────────────────────────────────

    public function test_second_warehouse_blocked_without_feature(): void
    {
        $this->fakeTenantWithFeatures(['multi_warehouse' => false]);
        Warehouse::create(['name' => 'Główny', 'city' => 'Warszawa', 'address' => 'ul. Testowa 1']);

        $response = $this->actingAs($this->manager(), 'tenant')
            ->withoutTenantMiddleware()
            ->post(route('tenant.manager.warehouses.store'), [
                'name' => 'Drugi', 'city' => 'Kraków', 'address' => 'ul. Testowa 2',
            ]);

        $response->assertSessionHasErrors();
        $this->assertEquals(1, Warehouse::count());
    }

    public function test_first_warehouse_allowed_without_feature(): void
    {
        $this->fakeTenantWithFeatures(['multi_warehouse' => false]);

        $this->actingAs($this->manager(), 'tenant')
            ->withoutTenantMiddleware()
            ->post(route('tenant.manager.warehouses.store'), [
                'name' => 'Główny', 'city' => 'Warszawa', 'address' => 'ul. Testowa 1',
            ])->assertSessionHasNoErrors();

        $this->assertEquals(1, Warehouse::count());
    }

    // ─── loyalty_program ────────────────────────────────────────────────

    public function test_points_not_awarded_without_loyalty_feature_even_if_setting_enabled(): void
    {
        $this->setSetting('loyalty_enabled', '1', 'boolean');
        $this->fakeTenantWithFeatures(['loyalty_program' => false]);

        $customer = Customer::create(['name' => 'Klient', 'email' => 'k@test.com', 'password' => bcrypt('x')]);
        $order = $this->createTestOrder([
            'customer_id' => $customer->id, 'payment_status' => 'paid', 'total' => 100,
        ]);

        app(LoyaltyService::class)->awardPointsForOrder($order);

        $this->assertEquals(0, $customer->fresh()->loyalty_points ?? 0);
    }

    // ─── email_campaigns ────────────────────────────────────────────────

    public function test_campaign_send_blocked_without_feature(): void
    {
        Mail::fake();
        $this->fakeTenantWithFeatures(['email_campaigns' => false]);
        $campaign = EmailCampaign::create([
            'name' => 'Test', 'subject' => 'Cześć', 'content' => 'Treść', 'status' => 'draft', 'target' => 'all',
        ]);

        $response = $this->actingAs($this->manager(), 'tenant')
            ->withoutTenantMiddleware()
            ->post(route('tenant.manager.marketing.send', $campaign));

        $response->assertSessionHasErrors();
        $this->assertEquals('draft', $campaign->fresh()->status);
    }

    // ─── analytics ──────────────────────────────────────────────────────

    public function test_reports_page_forbidden_without_analytics_feature(): void
    {
        $this->fakeTenantWithFeatures(['analytics' => false]);

        $this->actingAs($this->manager(), 'tenant')
            ->withoutTenantMiddleware()
            ->get(route('tenant.manager.reports.index'))
            ->assertForbidden();
    }

    public function test_reports_page_reachable_with_analytics_feature(): void
    {
        $this->fakeTenantWithFeatures(['analytics' => true]);

        $this->actingAs($this->manager(), 'tenant')
            ->withoutTenantMiddleware()
            ->get(route('tenant.manager.reports.index'))
            ->assertOk();
    }

    // ─── custom_css ─────────────────────────────────────────────────────

    public function test_custom_css_stripped_without_feature(): void
    {
        $this->fakeTenantWithFeatures(['custom_css' => false]);

        $this->actingAs($this->manager(), 'tenant')
            ->withoutTenantMiddleware()
            ->put(route('tenant.manager.settings.update'), [
                'shop_name' => 'Sklep',
                'custom_css' => 'body { display: none; }',
            ]);

        $this->assertNotEquals('body { display: none; }', Setting::get('custom_css'));
    }
}
