<?php

namespace Tests\Feature;

use App\Models\Tenant\Category;
use App\Models\Tenant\DiscountCode;
use App\Models\Tenant\Product;
use App\Models\Tenant\ProductVariant;
use App\Models\Tenant\ShippingMethod;
use Tests\TenantTestCase;

class OrderFlowTest extends TenantTestCase
{
    /**
     * Test complete order flow
     */
    public function test_customer_can_place_order(): void
    {
        // Setup: Create test data
        $category = Category::create([
            'name' => 'Pizza',
            'slug' => 'pizza',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Margherita',
            'slug' => 'margherita',
            'description' => 'Classic pizza',
            'price' => 25.00,
            'is_published' => true,
            'track_stock' => false,
        ]);

        $variant = ProductVariant::create([
            'product_id' => $product->id,
            'attributes' => [],
            'price' => 25.00,
            'sort_order' => 1,
            'is_active' => true,
        ]);

        $shippingMethod = $this->createTestShippingMethod();

        // Test: Submit order
        $response = $this->withoutTenantMiddleware()->postJson(route('tenant.checkout.store'), [
            'customer_name' => 'Jan Kowalski',
            'customer_email' => 'jan@example.com',
            'customer_phone' => '123456789',
            'payment_method' => 'cash_on_delivery',
            'terms_accepted' => true,
            'subtotal' => 50.00,
            'shipping_method_id' => $shippingMethod->id,
            'shipping_address' => ['country' => 'PL'],
            'items' => [
                [
                    'product_id' => $product->id,
                    'variant_id' => $variant->id,
                    'quantity' => 2,
                ],
            ],
        ]);

        // Assert: Order created successfully
        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
        ]);

        $this->assertDatabaseHas('orders', [
            'customer_name' => 'Jan Kowalski',
            'customer_phone' => '123456789',
            'payment_method' => 'cash_on_delivery',
            'status' => 'pending',
        ]);
    }

    /**
     * Test discount code application
     */
    public function test_discount_code_reduces_order_total(): void
    {
        // Setup
        $category = Category::create([
            'name' => 'Pizza',
            'slug' => 'pizza',
            'is_active' => true,
        ]);

        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Margherita',
            'slug' => 'margherita',
            'price' => 50.00,
            'is_published' => true,
            'track_stock' => false,
        ]);

        $variant = ProductVariant::create([
            'product_id' => $product->id,
            'attributes' => [],
            'price' => 50.00,
            'is_active' => true,
        ]);

        $discountCode = DiscountCode::create([
            'code' => 'TEST20',
            'type' => 'percentage',
            'value' => 20,
            'is_active' => true,
        ]);

        $shippingMethod = $this->createTestShippingMethod();

        // Test: Submit order with discount
        $response = $this->withoutTenantMiddleware()->postJson(route('tenant.checkout.store'), [
            'customer_name' => 'Jan Kowalski',
            'customer_email' => 'jan@example.com',
            'customer_phone' => '123456789',
            'payment_method' => 'cash_on_delivery',
            'terms_accepted' => true,
            'subtotal' => 50.00,
            'discount_code' => 'TEST20',
            'shipping_method_id' => $shippingMethod->id,
            'shipping_address' => ['country' => 'PL'],
            'items' => [
                [
                    'product_id' => $product->id,
                    'variant_id' => $variant->id,
                    'quantity' => 1,
                ],
            ],
        ]);

        // Assert: Discount applied correctly (50 - 20% = 40)
        $response->assertStatus(200);

        $this->assertDatabaseHas('orders', [
            'subtotal' => 50.00,
            'discount' => 10.00,
            'total' => 40.00,
        ]);

        // Assert: Discount code usage incremented
        $this->assertDatabaseHas('discount_codes', [
            'code' => 'TEST20',
            'used_count' => 1,
        ]);
    }

    /**
     * Test order validation
     */
    public function test_order_requires_customer_details(): void
    {
        $response = $this->withoutTenantMiddleware()->postJson(route('tenant.checkout.store'), [
            'items' => [],
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['customer_name', 'payment_method', 'items']);
    }

    public function test_order_rejects_shipping_method_not_attached_to_any_zone(): void
    {
        $category = Category::create(['name' => 'Pizza', 'slug' => 'pizza', 'is_active' => true]);
        $product = Product::create([
            'category_id' => $category->id, 'name' => 'Margherita', 'slug' => 'margherita',
            'price' => 25.00, 'is_published' => true, 'track_stock' => false,
        ]);
        $variant = ProductVariant::create([
            'product_id' => $product->id, 'attributes' => [], 'price' => 25.00, 'sort_order' => 1, 'is_active' => true,
        ]);

        // Method exists and is active, but was never attached to any zone —
        // calculateCost() must reject it instead of falling back to its base price.
        $orphanMethod = ShippingMethod::create([
            'name' => 'Orphan', 'type' => 'standard', 'price' => 5.00, 'is_active' => true,
        ]);

        $response = $this->withoutTenantMiddleware()->postJson(route('tenant.checkout.store'), [
            'customer_name' => 'Jan Kowalski',
            'customer_email' => 'jan@example.com',
            'customer_phone' => '123456789',
            'payment_method' => 'cash_on_delivery',
            'terms_accepted' => true,
            'subtotal' => 25.00,
            'shipping_method_id' => $orphanMethod->id,
            'shipping_address' => ['country' => 'PL'],
            'items' => [
                ['product_id' => $product->id, 'variant_id' => $variant->id, 'quantity' => 1],
            ],
        ]);

        $response->assertStatus(422);
    }

    public function test_order_rejects_digital_type_shipping_method_for_physical_cart(): void
    {
        $category = Category::create(['name' => 'Pizza', 'slug' => 'pizza', 'is_active' => true]);
        $product = Product::create([
            'category_id' => $category->id, 'name' => 'Margherita', 'slug' => 'margherita',
            'price' => 25.00, 'is_published' => true, 'track_stock' => false,
        ]);
        $variant = ProductVariant::create([
            'product_id' => $product->id, 'attributes' => [], 'price' => 25.00, 'sort_order' => 1, 'is_active' => true,
        ]);

        $digitalMethod = $this->createTestShippingMethod(['type' => 'digital', 'price' => 0]);

        $response = $this->withoutTenantMiddleware()->postJson(route('tenant.checkout.store'), [
            'customer_name' => 'Jan Kowalski',
            'customer_email' => 'jan@example.com',
            'customer_phone' => '123456789',
            'payment_method' => 'cash_on_delivery',
            'terms_accepted' => true,
            'subtotal' => 25.00,
            'shipping_method_id' => $digitalMethod->id,
            'shipping_address' => ['country' => 'PL'],
            'items' => [
                ['product_id' => $product->id, 'variant_id' => $variant->id, 'quantity' => 1],
            ],
        ]);

        $response->assertStatus(422);
    }
}
