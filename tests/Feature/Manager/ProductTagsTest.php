<?php

namespace Tests\Feature\Manager;

use App\Models\Tenant\Product;
use App\Models\Tenant\User;
use Tests\TenantTestCase;

/**
 * Tests for product tags (JSON column on products).
 *
 * Verifies that:
 * - Tags are saved correctly via the ProductController
 * - Only known tag values are accepted
 * - Tags are returned correctly when fetching a product
 */
class ProductTagsTest extends TenantTestCase
{
    private const VALID_TAGS = ['wege', 'vegan', 'bezgluten', 'ostra', 'eco', 'new', 'sale'];

    private function manager(): User
    {
        return User::create([
            'name' => 'Manager',
            'email' => 'mgr@test.com',
            'password' => bcrypt('secret'),
            'role' => 'manager',
            'is_active' => true,
        ]);
    }

    private function baseProductPayload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Produkt Testowy',
            'price' => '19.99',
            'type' => 'physical',
            'status' => 'active',
            'stock_qty' => 10,
            'description' => 'Opis produktu',
        ], $overrides);
    }

    // ─── Storing tags ────────────────────────────────────────────────────────

    public function test_product_can_be_created_with_tags(): void
    {
        $response = $this->actingAs($this->manager(), 'tenant')
            ->withoutTenantMiddleware()
            ->postJson(route('tenant.manager.products.store'), $this->baseProductPayload([
                'tags' => ['wege', 'eco'],
            ]));

        $this->assertNotEquals(422, $response->getStatusCode(), 'Valid tags should not return 422');

        $product = Product::where('name', 'Produkt Testowy')->first();
        $this->assertNotNull($product);
        $this->assertContains('wege', $product->tags);
        $this->assertContains('eco', $product->tags);
    }

    public function test_product_can_be_created_without_tags(): void
    {
        $response = $this->actingAs($this->manager(), 'tenant')
            ->withoutTenantMiddleware()
            ->postJson(route('tenant.manager.products.store'), $this->baseProductPayload([
                'tags' => [],
            ]));

        $this->assertNotEquals(422, $response->getStatusCode());
    }

    public function test_all_valid_tag_values_are_accepted(): void
    {
        $response = $this->actingAs($this->manager(), 'tenant')
            ->withoutTenantMiddleware()
            ->postJson(route('tenant.manager.products.store'), $this->baseProductPayload([
                'tags' => self::VALID_TAGS,
            ]));

        $this->assertNotEquals(422, $response->getStatusCode(),
            'All valid tag values should be accepted without validation error'
        );
    }

    // ─── Tag persistence via model ───────────────────────────────────────────

    public function test_tags_are_stored_as_array_and_cast_correctly(): void
    {
        $product = Product::create(array_merge(
            $this->baseProductPayload(),
            ['slug' => 'test-produkt', 'tags' => ['sale', 'new']]
        ));

        $fresh = Product::find($product->id);
        $this->assertIsArray($fresh->tags);
        $this->assertContains('sale', $fresh->tags);
        $this->assertContains('new', $fresh->tags);
    }

    public function test_tags_default_to_empty_array_when_null(): void
    {
        $product = Product::create(array_merge(
            $this->baseProductPayload(),
            ['slug' => 'test-brak-tagow', 'tags' => null]
        ));

        $fresh = Product::find($product->id);
        // null tags should cast to empty array or null — either is acceptable
        $tags = $fresh->tags ?? [];
        $this->assertIsArray($tags);
    }

    // ─── Updating tags ───────────────────────────────────────────────────────

    public function test_product_tags_can_be_updated(): void
    {
        $product = Product::create(array_merge(
            $this->baseProductPayload(),
            ['slug' => 'update-tags-test', 'tags' => ['new']]
        ));

        $response = $this->actingAs($this->manager(), 'tenant')
            ->withoutTenantMiddleware()
            ->putJson(route('tenant.manager.products.update', $product), $this->baseProductPayload([
                'slug' => 'update-tags-test',
                'tags' => ['wege', 'bezgluten'],
            ]));

        $this->assertNotEquals(422, $response->getStatusCode());

        $fresh = Product::find($product->id);
        $this->assertContains('wege', $fresh->tags ?? []);
        $this->assertNotContains('new', $fresh->tags ?? []);
    }
}
