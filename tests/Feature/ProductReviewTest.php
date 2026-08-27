<?php

namespace Tests\Feature;

use App\Models\Tenant\Customer;
use App\Models\Tenant\Product;
use App\Models\Tenant\ProductReview;
use Tests\TenantTestCase;

/**
 * The only live review form (ProductReviews.vue on the product page) never
 * sent order_id, so ReviewController::store()'s is_verified_purchase logic
 * — correctly written, order-ownership + product-in-items checked — never
 * actually activated for real traffic. Fixed by having ProductController::
 * show() compute the customer's own order containing this product and
 * ProductReviews.vue submitting it. Covers the controller-level logic
 * directly (the Vue-side wiring can't be exercised by PHPUnit).
 */
class ProductReviewTest extends TenantTestCase
{
    private function customer(): Customer
    {
        return Customer::create([
            'name' => 'Klient', 'email' => 'klient@test.com', 'password' => bcrypt('s'),
        ]);
    }

    private function product(): Product
    {
        return Product::create([
            'name' => 'Produkt', 'slug' => 'produkt-' . uniqid(),
            'sku' => 'P-' . uniqid(), 'price' => 50, 'is_active' => true, 'is_published' => true,
        ]);
    }

    public function test_review_with_own_order_containing_product_is_verified(): void
    {
        $customer = $this->customer();
        $product = $this->product();
        $order = $this->createTestOrder(['customer_id' => $customer->id]);
        $order->items()->create([
            'product_id' => $product->id, 'name' => $product->name,
            'price' => 50, 'quantity' => 1, 'total' => 50, 'product_type' => 'physical',
        ]);

        $this->actingAs($customer, 'customer')
            ->withoutTenantMiddleware()
            ->post(route('tenant.review.store'), [
                'product_id' => $product->id,
                'order_id' => $order->id,
                'rating' => 5,
                'body' => 'Świetny produkt!',
            ])->assertRedirect();

        $this->assertDatabaseHas('product_reviews', [
            'product_id' => $product->id,
            'customer_id' => $customer->id,
            'is_verified_purchase' => true,
        ]);
    }

    public function test_review_with_someone_elses_order_is_not_verified(): void
    {
        $customer = $this->customer();
        $other = Customer::create(['name' => 'Inny', 'email' => 'inny@test.com', 'password' => bcrypt('s')]);
        $product = $this->product();
        $order = $this->createTestOrder(['customer_id' => $other->id]);
        $order->items()->create([
            'product_id' => $product->id, 'name' => $product->name,
            'price' => 50, 'quantity' => 1, 'total' => 50, 'product_type' => 'physical',
        ]);

        $this->actingAs($customer, 'customer')
            ->withoutTenantMiddleware()
            ->post(route('tenant.review.store'), [
                'product_id' => $product->id,
                'order_id' => $order->id,
                'rating' => 5,
                'body' => 'Próba spoofingu',
            ])->assertRedirect();

        $this->assertDatabaseHas('product_reviews', [
            'product_id' => $product->id,
            'customer_id' => $customer->id,
            'is_verified_purchase' => false,
        ]);
    }

    public function test_duplicate_review_of_same_product_is_rejected(): void
    {
        $customer = $this->customer();
        $product = $this->product();
        ProductReview::create([
            'product_id' => $product->id, 'customer_id' => $customer->id,
            'rating' => 4, 'reviewer_name' => $customer->name, 'reviewer_email' => $customer->email,
            'is_verified_purchase' => false, 'is_approved' => false,
        ]);

        $response = $this->actingAs($customer, 'customer')
            ->withoutTenantMiddleware()
            ->post(route('tenant.review.store'), [
                'product_id' => $product->id,
                'rating' => 5,
                'body' => 'Druga próba',
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('error');
        $this->assertDatabaseCount('product_reviews', 1);
    }

    public function test_product_page_exposes_verified_order_id_for_logged_in_customer(): void
    {
        $customer = $this->customer();
        $product = $this->product();
        $order = $this->createTestOrder(['customer_id' => $customer->id]);
        $order->items()->create([
            'product_id' => $product->id, 'name' => $product->name,
            'price' => 50, 'quantity' => 1, 'total' => 50, 'product_type' => 'physical',
        ]);

        $response = $this->actingAs($customer, 'customer')
            ->withoutTenantMiddleware()
            ->get(route('tenant.product.show', $product));

        $response->assertInertia(fn ($page) => $page->where('verifiedOrderId', $order->id)
        );
    }

    public function test_product_page_verified_order_id_is_null_without_purchase(): void
    {
        $customer = $this->customer();
        $product = $this->product();

        $response = $this->actingAs($customer, 'customer')
            ->withoutTenantMiddleware()
            ->get(route('tenant.product.show', $product));

        $response->assertInertia(fn ($page) => $page->where('verifiedOrderId', null)
        );
    }
}
