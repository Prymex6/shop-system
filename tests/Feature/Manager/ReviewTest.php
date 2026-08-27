<?php

namespace Tests\Feature\Manager;

use App\Http\Middleware\Tenant\CheckSetupComplete;
use App\Http\Middleware\Tenant\CheckTenantLicense;
use App\Models\Tenant\Category;
use App\Models\Tenant\Product;
use App\Models\Tenant\ProductReview;
use App\Models\Tenant\User;
use Stancl\Tenancy\Middleware\InitializeTenancyByDomain;
use Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains;
use Tests\TenantTestCase;

/**
 * Tests for manager product review moderation (S19).
 *
 * Covers:
 *   S19.1.1 – Manager can view reviews list
 *   S19.1.2 – Manager can approve a review
 *   S19.1.3 – Manager can reject a review
 */
class ReviewTest extends TenantTestCase
{
    private function manager(): User
    {
        return User::create([
            'name' => 'Manager',
            'email' => 'mgr@test.com',
            'password' => bcrypt('s'),
            'role' => 'manager',
            'is_active' => true,
        ]);
    }

    private function makeReview(array $overrides = []): ProductReview
    {
        $cat = Category::create(['name' => 'Cat', 'slug' => 'cat-' . rand(), 'is_active' => true]);
        $product = Product::create([
            'category_id' => $cat->id,
            'name' => 'Product ' . rand(),
            'slug' => 'product-' . rand(),
            'price' => 29.99,
            'is_published' => true,
        ]);

        return ProductReview::create(array_merge([
            'product_id' => $product->id,
            'reviewer_name' => 'Anna Kowalska',
            'reviewer_email' => 'anna@test.com',
            'rating' => 5,
            'title' => 'Świetny produkt',
            'body' => 'Bardzo dobry produkt, polecam!',
            'is_approved' => false,
        ], $overrides));
    }

    // S19.1.1 – Manager sees list of reviews
    public function test_manager_can_view_reviews_list(): void
    {
        $this->makeReview(['is_approved' => true]);
        $this->makeReview(['is_approved' => false]);

        $response = $this->actingAs($this->manager(), 'tenant')
            ->withoutTenantMiddleware()
            ->get(route('tenant.manager.reviews.index'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->has('reviews')
        );
    }

    // S19.1.2 – Manager approves a review → is_approved becomes true
    public function test_manager_can_approve_review(): void
    {
        $review = $this->makeReview(['is_approved' => false]);

        $response = $this->actingAs($this->manager(), 'tenant')
            ->withoutTenantMiddleware()
            ->patchJson(route('tenant.manager.reviews.approve', $review));

        $response->assertRedirect();
        $review->refresh();
        $this->assertTrue($review->is_approved);
    }

    // S19.1.3 – Manager rejects a review → is_approved becomes false
    public function test_manager_can_reject_review(): void
    {
        $review = $this->makeReview(['is_approved' => true]);

        $response = $this->actingAs($this->manager(), 'tenant')
            ->withoutTenantMiddleware()
            ->patchJson(route('tenant.manager.reviews.reject', $review));

        $response->assertRedirect();
        $review->refresh();
        $this->assertFalse($review->is_approved);
    }

    // Manager can add a reply to a review
    public function test_manager_can_reply_to_review(): void
    {
        $review = $this->makeReview(['is_approved' => true]);

        $response = $this->actingAs($this->manager(), 'tenant')
            ->withoutTenantMiddleware()
            ->postJson(route('tenant.manager.reviews.reply', $review), [
                'reply' => 'Dziękujemy za opinię!',
            ]);

        $response->assertRedirect();
        $review->refresh();
        $this->assertEquals('Dziękujemy za opinię!', $review->reply);
    }

    // Manager can delete a review
    public function test_manager_can_delete_review(): void
    {
        $review = $this->makeReview();

        $response = $this->actingAs($this->manager(), 'tenant')
            ->withoutTenantMiddleware()
            ->delete(route('tenant.manager.reviews.destroy', $review));

        $response->assertRedirect();
        $this->assertDatabaseMissing('product_reviews', ['id' => $review->id]);
    }

    // Unauthenticated users cannot access manager reviews
    public function test_unauthenticated_cannot_access_reviews(): void
    {
        // Bypass only tenancy domain checks; keep auth middleware active
        $response = $this->withoutMiddleware([
            InitializeTenancyByDomain::class,
            PreventAccessFromCentralDomains::class,
            CheckSetupComplete::class,
            CheckTenantLicense::class,
        ])
            ->get(route('tenant.manager.reviews.index'));

        $response->assertRedirect();
    }
}
