<?php

namespace Tests;

use App\Http\Middleware\CheckPermission;
use App\Http\Middleware\CheckRole;
use App\Http\Middleware\EnsureTenantAuth;
use App\Http\Middleware\Tenant\CheckSetupComplete;
use App\Http\Middleware\Tenant\CheckTenantLicense;
use App\Models\Tenant\Order;
use App\Models\Tenant\Setting;
use App\Models\Tenant\ShippingMethod;
use App\Models\Tenant\ShippingZone;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Stancl\Tenancy\Middleware\InitializeTenancyByDomain;
use Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains;

/**
 * Base test case for tests that need tenant database tables.
 *
 * Uses ONLY tenant migrations (skipping landlord migrations) to avoid
 * table name conflicts (both landlord and tenant have a `users` table).
 *
 * With DB=:memory: SQLite, the app is recreated per test method, giving each
 * test a fresh empty database — so we always run `migrate` before each test.
 */
abstract class TenantTestCase extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // With DB_DATABASE=:memory: Laravel recreates a fresh PDO connection for each
        // test method (app is destroyed in tearDown and rebuilt in setUp). This means
        // every test starts with an empty SQLite database — always run migrations.
        Artisan::call('migrate', [
            '--path' => 'database/migrations/tenant',
            '--force' => true,
        ]);

        // Clear in-process setting cache so previous test values don't bleed through.
        Setting::flushRuntimeCache();

        // Mark install as complete so EnsureInstallComplete middleware lets all requests through.
        DB::table('tenant_settings')->insert([
            'key' => 'setup_completed',
            'value' => '1',
            'type' => 'boolean',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Truncate all tenant tables to reset state between tests.
     */
    protected function truncateTenantTables(): void
    {
        $tables = [
            'orders', 'order_items', 'products', 'product_variants',
            'categories', 'users', 'customers', 'discount_codes',
            'tenant_settings', 'staff_reports', 'role_permissions',
            'loyalty_points', 'email_campaigns', 'push_subscriptions',
            'abandoned_carts', 'gift_cards', 'promotions', 'flash_sales',
            'collections', 'product_bundles', 'warehouses', 'shipments',
            'rma_requests', 'badges', 'wishlists',
        ];

        DB::statement('PRAGMA foreign_keys = OFF');
        foreach ($tables as $table) {
            if (DB::getSchemaBuilder()->hasTable($table)) {
                DB::table($table)->truncate();
            }
        }
        DB::statement('PRAGMA foreign_keys = ON');
    }

    /**
     * Return HTTP middleware to bypass in tenant tests.
     * Auth is handled by actingAs(), so EnsureTenantAuth and CheckRole are excluded.
     */
    protected function tenantMiddlewareToExclude(): array
    {
        return [
            InitializeTenancyByDomain::class,
            PreventAccessFromCentralDomains::class,
            CheckSetupComplete::class,
            CheckTenantLicense::class,
            EnsureTenantAuth::class,
            CheckRole::class,
            CheckPermission::class,
        ];
    }

    /**
     * Make a request bypassing all tenant middleware.
     */
    protected function withoutTenantMiddleware(): static
    {
        return $this->withoutMiddleware($this->tenantMiddlewareToExclude());
    }

    /**
     * Seed a setting into tenant_settings table.
     */
    protected function setSetting(string $key, mixed $value, string $type = 'string'): void
    {
        DB::table('tenant_settings')->upsert(
            [['key' => $key, 'value' => (string) $value, 'type' => $type, 'created_at' => now(), 'updated_at' => now()]],
            ['key'],
            ['value', 'type', 'updated_at']
        );
    }

    /**
     * Seed multiple settings at once.
     */
    protected function setSettings(array $settings): void
    {
        foreach ($settings as $key => $value) {
            $this->setSetting($key, $value);
        }
    }

    /**
     * Fake the tenant version for tests that depend on stable/test gating.
     */
    protected function asTenantVersion(string $version): void
    {
        $fakeTenant = new \stdClass;
        $fakeTenant->version = $version;
        tenancy()->tenant = $fakeTenant;
    }

    /**
     * Create a minimal Order for payment tests.
     */
    protected function createTestOrder(array $overrides = []): Order
    {
        $defaults = [
            'order_number' => 'TEST-' . rand(1000, 9999),
            'customer_name' => 'Jan Testowy',
            'customer_email' => 'test@example.com',
            'customer_phone' => '123456789',
            'subtotal' => 50.00,
            'shipping_cost' => 0,
            'discount' => 0,
            'tax' => 0,
            'total' => 50.00,
            'status' => 'pending',
            'fulfillment_status' => 'unfulfilled',
            'payment_method' => 'przelewy24',
            'payment_status' => 'awaiting_payment',
            'payment_data' => null,
            'tracking_token' => Str::random(32),
        ];

        return Order::create(array_merge($defaults, $overrides));
    }

    /**
     * Create a shipping method attached to a default zone, so checkout tests
     * for physical products (the default Product type) can submit a
     * shipping_method_id that ShippingCalculatorService actually accepts —
     * calculateCost() rejects any method not attached to the resolved zone.
     */
    protected function createTestShippingMethod(array $overrides = []): ShippingMethod
    {
        $zone = ShippingZone::firstOrCreate(
            ['is_default' => true],
            ['name' => 'Default Zone', 'countries' => ['PL']]
        );

        $method = ShippingMethod::create(array_merge([
            'name' => 'Kurier',
            'type' => 'standard',
            'price' => 0,
            'is_active' => true,
        ], $overrides));

        $zone->methods()->attach($method->id);

        return $method;
    }
}
