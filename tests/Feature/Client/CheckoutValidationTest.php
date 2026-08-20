<?php

namespace Tests\Feature\Client;

use App\Models\Tenant\Category;
use App\Models\Tenant\DiscountCode;
use App\Models\Tenant\Product;
use App\Models\Tenant\ProductVariant;
use Tests\TenantTestCase;

/**
 * Tests for checkout validation (discount codes, address validation).
 */
class CheckoutValidationTest extends TenantTestCase
{
    private function setupProduct(): array
    {
        $cat = Category::create(['name' => 'Pizza', 'slug' => 'pizza', 'is_active' => true]);
        $product = Product::create(['category_id' => $cat->id, 'name' => 'M', 'slug' => 'm', 'price' => 30.00, 'is_published' => true]);
        $variant = ProductVariant::create(['product_id' => $product->id, 'attributes' => [], 'price' => 30.00, 'sort_order' => 1]);

        return [$product, $variant];
    }

    // ─── Discount code validation ──────────────────────────────────────

    public function test_valid_discount_code_returns_discount_amount(): void
    {
        DiscountCode::create(['code' => 'VALID10', 'type' => 'percentage', 'value' => 10, 'is_active' => true]);

        $response = $this->withoutTenantMiddleware()
            ->postJson(route('tenant.checkout.validate-discount'), [
                'code' => 'VALID10',
                'subtotal' => 100.00,
            ]);

        $response->assertStatus(200);
        $response->assertJson(['valid' => true]);
        $response->assertJsonPath('discount.amount', 10);
    }

    public function test_invalid_discount_code_returns_error(): void
    {
        $response = $this->withoutTenantMiddleware()
            ->postJson(route('tenant.checkout.validate-discount'), [
                'code' => 'NONEXISTENT',
                'subtotal' => 100.00,
            ]);

        $response->assertStatus(422);
        $response->assertJson(['valid' => false]);
    }

    public function test_inactive_discount_code_is_rejected(): void
    {
        DiscountCode::create(['code' => 'INACTIVE', 'type' => 'fixed', 'value' => 5, 'is_active' => false]);

        $response = $this->withoutTenantMiddleware()
            ->postJson(route('tenant.checkout.validate-discount'), [
                'code' => 'INACTIVE',
                'subtotal' => 50.00,
            ]);

        $response->assertJson(['valid' => false]);
    }

    public function test_discount_code_respects_min_order_value(): void
    {
        DiscountCode::create([
            'code' => 'MINORDER',
            'type' => 'fixed',
            'value' => 10,
            'min_order_value' => 50.00,
            'is_active' => true,
        ]);

        $response = $this->withoutTenantMiddleware()
            ->postJson(route('tenant.checkout.validate-discount'), [
                'code' => 'MINORDER',
                'subtotal' => 30.00, // Below minimum
            ]);

        $response->assertJson(['valid' => false]);
    }

    public function test_discount_code_accepts_when_above_minimum(): void
    {
        DiscountCode::create([
            'code' => 'MINORDER',
            'type' => 'fixed',
            'value' => 10,
            'min_order_value' => 50.00,
            'is_active' => true,
        ]);

        $response = $this->withoutTenantMiddleware()
            ->postJson(route('tenant.checkout.validate-discount'), [
                'code' => 'MINORDER',
                'subtotal' => 60.00,
            ]);

        $response->assertJson(['valid' => true]);
        $response->assertJsonPath('discount.amount', 10);
    }

    public function test_expired_discount_code_is_rejected(): void
    {
        DiscountCode::create([
            'code' => 'EXPIRED',
            'type' => 'fixed',
            'value' => 5,
            'valid_until' => now()->subDays(1),
            'is_active' => true,
        ]);

        $response = $this->withoutTenantMiddleware()
            ->postJson(route('tenant.checkout.validate-discount'), [
                'code' => 'EXPIRED',
                'subtotal' => 50.00,
            ]);

        $response->assertJson(['valid' => false]);
    }

    public function test_fixed_discount_cannot_exceed_subtotal(): void
    {
        DiscountCode::create(['code' => 'BIG', 'type' => 'fixed', 'value' => 100, 'is_active' => true]);

        $response = $this->withoutTenantMiddleware()
            ->postJson(route('tenant.checkout.validate-discount'), [
                'code' => 'BIG',
                'subtotal' => 20.00,
            ]);

        $response->assertStatus(200);
        // Discount should be capped at subtotal value
        $data = $response->json();
        if (isset($data['valid']) && $data['valid']) {
            $this->assertLessThanOrEqual(20.00, $data['discount']['amount']);
        }
    }

    // ─── Order validation ─────────────────────────────────────────────

    public function test_checkout_requires_terms_accepted(): void
    {
        [$product, $variant] = $this->setupProduct();

        $response = $this->withoutTenantMiddleware()
            ->postJson(route('tenant.checkout.store'), [
                'customer_name' => 'Test',
                'customer_email' => 'test@test.com',
                'customer_phone' => '123456789',
                'payment_method' => 'cash_on_delivery',
                // Missing terms_accepted
                'items' => [
                    ['product_id' => $product->id, 'variant_id' => $variant->id, 'quantity' => 1],
                ],
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['terms_accepted']);
    }

    public function test_checkout_rejects_nonexistent_product_id(): void
    {
        $response = $this->withoutTenantMiddleware()
            ->postJson(route('tenant.checkout.store'), [
                'type' => 'pickup',
                'customer_name' => 'Test',
                'customer_email' => 'test@test.com',
                'customer_phone' => '123456789',
                'payment_method' => 'cash_on_delivery',
                'items' => [
                    ['product_id' => 99999, 'variant_id' => 99999, 'quantity' => 1],
                ],
            ]);

        $response->assertStatus(422);
    }
}
