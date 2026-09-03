<?php

namespace Tests\Feature;

use App\Models\Tenant\Order;
use App\Models\Tenant\Product;
use Tests\TenantTestCase;

/**
 * Plan::max_orders_per_month was only ever displayed on the License page —
 * nothing on the tenant side actually blocked placing an order past it, so
 * a tenant downgraded below their current volume saw no effect at all.
 * CheckoutController::store() now checks it via tenancy()->tenant->plan
 * (Landlord\Tenant declares its own 'central' connection, so this works
 * without switching tenancy context). Faking tenancy()->tenant here mirrors
 * TenantTestCase::asTenantVersion()'s established pattern for tests that
 * need tenancy()->tenant populated without a real landlord DB.
 */
class PlanOrderLimitTest extends TenantTestCase
{
    private function fakeTenantWithOrderLimit(?int $limit): void
    {
        $plan = new \stdClass;
        $plan->max_orders_per_month = $limit;

        // getAttribute() is required — the tenant('id') helper used elsewhere
        // in the request pipeline calls it, not plain property access (see
        // ChatChannelSecurityTest::bindFakeTenant() for the same pattern).
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

    private function product(): Product
    {
        return Product::create([
            'name' => 'Produkt', 'slug' => 'produkt-' . uniqid(),
            'sku' => 'P-' . uniqid(), 'price' => 50, 'is_active' => true,
            'is_published' => true, 'track_stock' => false,
        ]);
    }

    private function payload(Product $product, array $overrides = []): array
    {
        $shippingMethod = $this->createTestShippingMethod();

        return array_merge([
            'customer_name' => 'Jan Kowalski',
            'customer_email' => 'jan-' . uniqid() . '@example.com',
            'customer_phone' => '123456789',
            'payment_method' => 'cash_on_delivery',
            'terms_accepted' => true,
            'shipping_method_id' => $shippingMethod->id,
            'shipping_address' => ['country' => 'PL'],
            'items' => [
                ['product_id' => $product->id, 'quantity' => 1],
            ],
        ], $overrides);
    }

    public function test_order_is_blocked_once_monthly_plan_limit_is_reached(): void
    {
        $this->fakeTenantWithOrderLimit(1);
        $product = $this->product();

        $this->withoutTenantMiddleware()
            ->postJson(route('tenant.checkout.store'), $this->payload($product))
            ->assertOk();

        $response = $this->withoutTenantMiddleware()
            ->postJson(route('tenant.checkout.store'), $this->payload($product));

        $response->assertStatus(422);
        $this->assertEquals(1, Order::count());
    }

    public function test_order_is_allowed_when_under_the_limit(): void
    {
        $this->fakeTenantWithOrderLimit(5);
        $product = $this->product();

        $this->withoutTenantMiddleware()
            ->postJson(route('tenant.checkout.store'), $this->payload($product))
            ->assertOk();

        $this->assertEquals(1, Order::count());
    }

    public function test_null_limit_means_unlimited(): void
    {
        $this->fakeTenantWithOrderLimit(null);
        $product = $this->product();

        $this->withoutTenantMiddleware()
            ->postJson(route('tenant.checkout.store'), $this->payload($product))
            ->assertOk();
        $this->withoutTenantMiddleware()
            ->postJson(route('tenant.checkout.store'), $this->payload($product))
            ->assertOk();

        $this->assertEquals(2, Order::count());
    }
}
