<?php

namespace Tests\Feature\Manager;

use App\Models\Tenant\Product;
use App\Models\Tenant\ProductVariant;
use App\Models\Tenant\User;
use Tests\TenantTestCase;

/**
 * product_variants.sku had no uniqueness check, and two variants of the
 * same product could share an identical attribute combination (e.g. two
 * "Czerwony / L") — the storefront's VariantSelector silently picked the
 * first match, leaving the second permanently unselectable with orphaned
 * stock. Both are now guarded in ProductController::storeVariant/updateVariant.
 */
class ProductVariantUniquenessTest extends TenantTestCase
{
    private function manager(): User
    {
        return User::create([
            'name' => 'Manager', 'email' => 'manager@test.com',
            'password' => bcrypt('secret'), 'role' => 'manager', 'is_active' => true,
        ]);
    }

    private function product(): Product
    {
        return Product::create([
            'name' => 'Koszulka', 'slug' => 'koszulka-' . uniqid(),
            'sku' => 'KOSZ-' . uniqid(), 'price' => 50, 'is_active' => true,
        ]);
    }

    public function test_duplicate_variant_sku_is_rejected(): void
    {
        $product = $this->product();
        ProductVariant::create([
            'product_id' => $product->id, 'sku' => 'VAR-001',
            'attributes' => ['color' => 'red'], 'stock_quantity' => 5,
        ]);

        $response = $this->actingAs($this->manager(), 'tenant')
            ->withoutTenantMiddleware()
            ->postJson(route('tenant.manager.products.variants.store', $product), [
                'sku' => 'VAR-001',
                'attributes' => ['color' => 'blue'],
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['sku']);
    }

    public function test_updating_variant_can_keep_its_own_sku(): void
    {
        $product = $this->product();
        $variant = ProductVariant::create([
            'product_id' => $product->id, 'sku' => 'VAR-001',
            'attributes' => ['color' => 'red'], 'stock_quantity' => 5,
        ]);

        $response = $this->actingAs($this->manager(), 'tenant')
            ->withoutTenantMiddleware()
            ->putJson(route('tenant.manager.products.variants.update', [$product, $variant]), [
                'sku' => 'VAR-001',
                'attributes' => ['color' => 'red'],
                'stock_quantity' => 9,
            ]);

        $response->assertOk();
    }

    public function test_duplicate_attribute_combination_is_rejected(): void
    {
        $product = $this->product();
        ProductVariant::create([
            'product_id' => $product->id, 'sku' => 'VAR-001',
            'attributes' => ['color' => 'red', 'size' => 'L'], 'stock_quantity' => 5,
        ]);

        $response = $this->actingAs($this->manager(), 'tenant')
            ->withoutTenantMiddleware()
            ->postJson(route('tenant.manager.products.variants.store', $product), [
                'sku' => 'VAR-002',
                'attributes' => ['size' => 'L', 'color' => 'red'],
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['attributes']);
    }

    public function test_different_attribute_combination_is_accepted(): void
    {
        $product = $this->product();
        ProductVariant::create([
            'product_id' => $product->id, 'sku' => 'VAR-001',
            'attributes' => ['color' => 'red', 'size' => 'L'], 'stock_quantity' => 5,
        ]);

        $response = $this->actingAs($this->manager(), 'tenant')
            ->withoutTenantMiddleware()
            ->postJson(route('tenant.manager.products.variants.store', $product), [
                'sku' => 'VAR-002',
                'attributes' => ['color' => 'red', 'size' => 'M'],
            ]);

        $response->assertOk();
    }
}
