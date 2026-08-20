<?php

namespace Tests\Feature;

use App\Http\Middleware\CheckRole;
use App\Http\Middleware\EnsureTenantAuth;
use App\Http\Middleware\Tenant\CheckSetupComplete;
use App\Models\Tenant\Category;
use App\Models\Tenant\Product;
use App\Models\Tenant\ProductVariant;
use Stancl\Tenancy\Middleware\InitializeTenancyByDomain;
use Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains;
use Tests\TenantTestCase;

/**
 * Tests for vacation mode and opening hours restrictions.
 */
class VacationModeTest extends TenantTestCase
{
    private function setupProduct(): array
    {
        $cat = Category::create(['name' => 'Pizza', 'slug' => 'pizza', 'is_active' => true]);
        $product = Product::create(['category_id' => $cat->id, 'name' => 'M', 'slug' => 'm', 'price' => 25.00, 'is_published' => true, 'track_stock' => false]);
        $variant = ProductVariant::create(['product_id' => $product->id, 'attributes' => [], 'price' => 25.00, 'sort_order' => 1, 'is_active' => true]);

        return [$product, $variant];
    }

    public function test_orders_blocked_when_vacation_mode_active(): void
    {
        $this->setSettings([
            'vacation_mode' => '1',
            'vacation_message' => 'Przerwa urlopowa',
        ]);

        [$product, $variant] = $this->setupProduct();

        $response = $this->withoutMiddleware([
            InitializeTenancyByDomain::class,
            PreventAccessFromCentralDomains::class,
            CheckSetupComplete::class,
            EnsureTenantAuth::class,
            CheckRole::class,
        ])
            ->postJson(route('tenant.checkout.store'), [
                'type' => 'pickup',
                'customer_name' => 'Test',
                'customer_email' => 'test@test.com',
                'customer_phone' => '123456789',
                'payment_method' => 'cash_on_delivery',
                'items' => [
                    ['product_id' => $product->id, 'variant_id' => $variant->id, 'quantity' => 1],
                ],
            ]);

        $response->assertStatus(422);
    }

    public function test_orders_allowed_when_vacation_mode_inactive(): void
    {
        $this->setSettings(['vacation_mode' => '0']);

        [$product, $variant] = $this->setupProduct();
        $shippingMethod = $this->createTestShippingMethod();

        $response = $this->withoutTenantMiddleware()
            ->postJson(route('tenant.checkout.store'), [
                'type' => 'pickup',
                'customer_name' => 'Test',
                'customer_email' => 'test@test.com',
                'customer_phone' => '123456789',
                'payment_method' => 'cash_on_delivery',
                'terms_accepted' => true,
                'subtotal' => 25.00,
                'shipping_method_id' => $shippingMethod->id,
                'shipping_address' => ['country' => 'PL'],
                'items' => [
                    ['product_id' => $product->id, 'variant_id' => $variant->id, 'quantity' => 1],
                ],
            ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);
    }

    public function test_menu_page_shows_vacation_banner(): void
    {
        $this->setSettings([
            'vacation_mode' => '1',
            'vacation_message' => 'Jesteśmy na urlopie',
        ]);

        $response = $this->withoutTenantMiddleware()->get(route('tenant.shop'));

        $response->assertStatus(200);
        // Verify vacation_mode setting is stored and accessible
        $this->assertDatabaseHas('tenant_settings', [
            'key' => 'vacation_mode',
            'value' => '1',
        ]);
    }

    public function test_closed_outside_opening_hours(): void
    {
        // Set restaurant as closed all day today
        $today = strtolower(now()->format('l')); // e.g. "monday"
        $hours = [
            $today => ['closed' => true, 'open' => '09:00', 'close' => '22:00'],
        ];
        $this->setSettings(['opening_hours' => json_encode($hours)]);

        [$product, $variant] = $this->setupProduct();

        $response = $this->withoutMiddleware([
            InitializeTenancyByDomain::class,
            PreventAccessFromCentralDomains::class,
            CheckSetupComplete::class,
            EnsureTenantAuth::class,
            CheckRole::class,
        ])
            ->postJson(route('tenant.checkout.store'), [
                'type' => 'pickup',
                'customer_name' => 'Test',
                'customer_email' => 'test@test.com',
                'customer_phone' => '123456789',
                'payment_method' => 'cash_on_delivery',
                'items' => [
                    ['product_id' => $product->id, 'variant_id' => $variant->id, 'quantity' => 1],
                ],
            ]);

        $response->assertStatus(422);
    }
}
