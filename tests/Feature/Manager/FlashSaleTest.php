<?php

namespace Tests\Feature\Manager;

use App\Models\Tenant\FlashSale;
use App\Models\Tenant\Product;
use App\Models\Tenant\User;
use Tests\TenantTestCase;

/**
 * Tests for flash sale management.
 */
class FlashSaleTest extends TenantTestCase
{
    private function manager(): User
    {
        return User::create([
            'name' => 'Manager', 'email' => 'mgr@test.com',
            'password' => bcrypt('s'), 'role' => 'manager', 'is_active' => true,
        ]);
    }

    private function flashSaleData(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Summer Sale',
            'discount_type' => 'percentage',
            'discount_value' => 20,
            'starts_at' => now()->addHour()->format('Y-m-d H:i:s'),
            'ends_at' => now()->addDay()->format('Y-m-d H:i:s'),
        ], $overrides);
    }

    public function test_manager_can_view_flash_sales(): void
    {
        $response = $this->actingAs($this->manager(), 'tenant')
            ->withoutTenantMiddleware()
            ->get(route('tenant.manager.flash-sales.index'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('Tenant/Manager/FlashSales/Index')
            ->has('flashSales')
        );
    }

    public function test_manager_can_create_flash_sale(): void
    {
        $response = $this->actingAs($this->manager(), 'tenant')
            ->withoutTenantMiddleware()
            ->postJson(route('tenant.manager.flash-sales.store'), $this->flashSaleData());

        $response->assertRedirect();
        $this->assertDatabaseHas('flash_sales', ['name' => 'Summer Sale', 'discount_type' => 'percentage', 'discount_value' => 20]);
    }

    public function test_manager_can_update_flash_sale(): void
    {
        $sale = FlashSale::create($this->flashSaleData(['name' => 'Old Sale']));

        $response = $this->actingAs($this->manager(), 'tenant')
            ->withoutTenantMiddleware()
            ->putJson(route('tenant.manager.flash-sales.update', $sale), $this->flashSaleData([
                'name' => 'Updated Sale',
                'discount_value' => 30,
            ]));

        $response->assertRedirect();
        $this->assertDatabaseHas('flash_sales', ['id' => $sale->id, 'name' => 'Updated Sale', 'discount_value' => 30]);
    }

    public function test_manager_can_delete_flash_sale(): void
    {
        $sale = FlashSale::create($this->flashSaleData(['name' => 'To Delete']));

        $response = $this->actingAs($this->manager(), 'tenant')
            ->withoutTenantMiddleware()
            ->deleteJson(route('tenant.manager.flash-sales.destroy', $sale));

        $response->assertRedirect();
        $this->assertDatabaseMissing('flash_sales', ['id' => $sale->id]);
    }

    public function test_flash_sale_requires_valid_dates(): void
    {
        // ends_at must be after starts_at
        $response = $this->actingAs($this->manager(), 'tenant')
            ->withoutTenantMiddleware()
            ->postJson(route('tenant.manager.flash-sales.store'), [
                'name' => 'Bad Dates',
                'discount_type' => 'percentage',
                'discount_value' => 10,
                'starts_at' => now()->addDay()->format('Y-m-d H:i:s'),
                'ends_at' => now()->format('Y-m-d H:i:s'),
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['ends_at']);
    }

    public function test_manager_can_add_products_to_flash_sale(): void
    {
        $sale = FlashSale::create($this->flashSaleData());

        $product = Product::create([
            'name' => 'P',
            'slug' => 'p',
            'price' => 9.99,
            'type' => 'physical',
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->manager(), 'tenant')
            ->withoutTenantMiddleware()
            ->postJson(route('tenant.manager.flash-sales.products.add', $sale), [
                'products' => [
                    ['product_id' => $product->id, 'custom_price' => null],
                ],
            ]);

        $response->assertRedirect();
        $this->assertTrue($sale->products()->where('products.id', $product->id)->exists());
    }

    public function test_manager_can_sync_flash_sale_product_list(): void
    {
        $sale = FlashSale::create($this->flashSaleData());

        $kept = Product::create(['name' => 'Kept', 'slug' => 'kept', 'price' => 9.99, 'type' => 'physical', 'status' => 'active']);
        $dropped = Product::create(['name' => 'Dropped', 'slug' => 'dropped', 'price' => 9.99, 'type' => 'physical', 'status' => 'active']);
        $added = Product::create(['name' => 'Added', 'slug' => 'added', 'price' => 9.99, 'type' => 'physical', 'status' => 'active']);

        $sale->products()->sync([$kept->id, $dropped->id]);

        // The "manage products" modal sends the full desired list — this is
        // what was missing a backend route/method entirely (fixed alongside
        // the addProducts()/removeProduct() single-item endpoints).
        $response = $this->actingAs($this->manager(), 'tenant')
            ->withoutTenantMiddleware()
            ->patchJson(route('tenant.manager.flash-sales.products', $sale), [
                'product_ids' => [$kept->id, $added->id],
            ]);

        $response->assertRedirect();
        $ids = $sale->products()->pluck('products.id')->all();
        $this->assertContains($kept->id, $ids);
        $this->assertContains($added->id, $ids);
        $this->assertNotContains($dropped->id, $ids);
    }
}
