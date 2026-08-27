<?php

namespace Tests\Feature;

use App\Http\Middleware\CheckRole;
use App\Http\Middleware\EnsureTenantAuth;
use App\Http\Middleware\Tenant\CheckSetupComplete;
use App\Http\Middleware\Tenant\CheckShopOpen;
use App\Models\Tenant\Category;
use App\Models\Tenant\Product;
use App\Models\Tenant\ProductVariant;
use Illuminate\Support\Facades\Cache;
use Illuminate\Testing\TestResponse;
use Stancl\Tenancy\Middleware\InitializeTenancyByDomain;
use Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains;
use Tests\TenantTestCase;

/**
 * Tests for checkout rate limiting (throttle:5,60 on POST /checkout).
 */
class RateLimitTest extends TenantTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // Flush array cache to reset throttle counters between tests
        Cache::flush();
    }

    private function setupProduct(): array
    {
        $cat = Category::create(['name' => 'P', 'slug' => 'p', 'is_active' => true]);
        $product = Product::create(['category_id' => $cat->id, 'name' => 'P', 'slug' => 'prod', 'price' => 10.00, 'is_published' => true]);
        $variant = ProductVariant::create(['product_id' => $product->id, 'attributes' => [], 'price' => 10.00, 'sort_order' => 1]);

        return [$product, $variant];
    }

    private function makeCheckoutRequest(array $product_ids): TestResponse
    {
        [$product, $variant] = $product_ids;

        return $this->withoutMiddleware([
            InitializeTenancyByDomain::class,
            PreventAccessFromCentralDomains::class,
            CheckSetupComplete::class,
            EnsureTenantAuth::class,
            CheckRole::class,
            CheckShopOpen::class,
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
    }

    public function test_checkout_allows_up_to_5_requests(): void
    {
        $product = $this->setupProduct();

        for ($i = 0; $i < 5; $i++) {
            $response = $this->makeCheckoutRequest($product);
            // Each should succeed (200) or fail for business reasons, but not 429
            $this->assertNotEquals(429, $response->getStatusCode(), "Request $i should not be rate limited");
        }
    }

    public function test_checkout_rate_limits_after_5_requests(): void
    {
        $product = $this->setupProduct();

        // Make 5 successful requests
        for ($i = 0; $i < 5; $i++) {
            $this->makeCheckoutRequest($product);
        }

        // 6th request should be rate limited
        $response = $this->makeCheckoutRequest($product);
        $response->assertStatus(429);
    }
}
