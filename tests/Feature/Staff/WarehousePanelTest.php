<?php

namespace Tests\Feature\Staff;

use App\Models\Tenant\Product;
use App\Models\Tenant\ProductWarehouseStock;
use App\Models\Tenant\User;
use App\Models\Tenant\Warehouse;
use Tests\TenantTestCase;

/**
 * The warehouse page staff open to see what is on the shelves.
 *
 * It answered 500 for as long as it existed: the controller eager-loaded a
 * relation called "stocks" where the model declares "stock", so Eloquent threw
 * "Call to undefined relationship" before anything rendered. Nothing caught it
 * because nothing asked for the page.
 */
class WarehousePanelTest extends TenantTestCase
{
    private function warehouseStaff(): User
    {
        return User::create([
            'name' => 'Stock',
            'email' => 'stock@test.com',
            'password' => bcrypt('s'),
            'role' => 'warehouse',
            'is_active' => true,
        ]);
    }

    public function test_the_warehouse_page_opens(): void
    {
        $this->actingAs($this->warehouseStaff(), 'tenant')
            ->withoutTenantMiddleware()
            ->get(route('tenant.staff.warehouse'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('Tenant/Staff/Warehouse')
                ->has('warehouses')
                ->has('lowStock')
            );
    }

    public function test_each_warehouse_arrives_with_its_stock_and_the_products_on_it(): void
    {
        $warehouse = Warehouse::create([
            'name' => 'Main',
            'city' => 'Warsaw',
            'address' => '1 Example St',
            'is_active' => true,
        ]);

        $product = Product::create([
            'name' => 'Widget',
            'slug' => 'widget',
            'sku' => 'W-1',
            'price' => 20,
            'is_active' => true,
            'is_published' => true,
            'track_stock' => true,
            'stock_quantity' => 7,
        ]);

        ProductWarehouseStock::create([
            'warehouse_id' => $warehouse->id,
            'product_id' => $product->id,
            'quantity' => 7,
        ]);

        $this->actingAs($this->warehouseStaff(), 'tenant')
            ->withoutTenantMiddleware()
            ->get(route('tenant.staff.warehouse'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('warehouses.0.stock.0.quantity', 7)
                ->where('warehouses.0.stock.0.product.name', 'Widget')
            );
    }
}
