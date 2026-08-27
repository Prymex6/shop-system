<?php

namespace Tests\Feature\Manager;

use App\Models\Tenant\Product;
use App\Models\Tenant\ProductWarehouseStock;
use App\Models\Tenant\User;
use App\Models\Tenant\Warehouse;
use Tests\TenantTestCase;

/**
 * Tests for warehouse CRUD management and stock adjustment.
 *
 * Covers:
 *   – Manager can view the warehouses list
 *   – Manager can create, update and delete a warehouse
 *   – Manager can view stock for a specific warehouse
 *   – Manager can submit a stock adjustment
 */
class WarehouseTest extends TenantTestCase
{
    private function manager(): User
    {
        return User::create([
            'name' => 'Manager', 'email' => 'mgr@test.com',
            'password' => bcrypt('s'), 'role' => 'manager', 'is_active' => true,
        ]);
    }

    public function test_manager_can_view_warehouses(): void
    {
        $response = $this->actingAs($this->manager(), 'tenant')
            ->withoutTenantMiddleware()
            ->get(route('tenant.manager.warehouses.index'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('Tenant/Manager/Warehouses/Index')
            ->has('warehouses')
        );
    }

    public function test_manager_can_create_warehouse(): void
    {
        $response = $this->actingAs($this->manager(), 'tenant')
            ->withoutTenantMiddleware()
            ->postJson(route('tenant.manager.warehouses.store'), [
                'name' => 'Main Warehouse',
                'city' => 'Warsaw',
                'address' => 'ul. Testowa 1, 00-001 Warszawa',
                'is_active' => true,
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('warehouses', [
            'name' => 'Main Warehouse',
            'city' => 'Warsaw',
        ]);
    }

    public function test_manager_can_update_warehouse(): void
    {
        $warehouse = Warehouse::create([
            'name' => 'Old Name',
            'city' => 'Kraków',
            'address' => 'ul. Stara 5',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->manager(), 'tenant')
            ->withoutTenantMiddleware()
            ->putJson(route('tenant.manager.warehouses.update', $warehouse), [
                'name' => 'New Name',
                'city' => 'Gdańsk',
                'address' => 'ul. Nowa 10',
                'is_active' => true,
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('warehouses', [
            'id' => $warehouse->id,
            'name' => 'New Name',
            'city' => 'Gdańsk',
        ]);
    }

    public function test_manager_can_delete_warehouse(): void
    {
        $warehouse = Warehouse::create([
            'name' => 'To Delete',
            'city' => 'Poznań',
            'address' => 'ul. Usuwalna 1',
            'is_active' => false,
        ]);

        $response = $this->actingAs($this->manager(), 'tenant')
            ->withoutTenantMiddleware()
            ->deleteJson(route('tenant.manager.warehouses.destroy', $warehouse));

        $response->assertRedirect();
        $this->assertDatabaseMissing('warehouses', ['id' => $warehouse->id]);
    }

    public function test_manager_can_view_warehouse_stock(): void
    {
        $warehouse = Warehouse::create([
            'name' => 'Stock Warehouse',
            'city' => 'Łódź',
            'address' => 'ul. Stockowa 1',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->manager(), 'tenant')
            ->withoutTenantMiddleware()
            ->get(route('tenant.manager.warehouses.stock', $warehouse));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('Tenant/Manager/Warehouses/Stock')
            ->has('stockItems')
        );
    }

    public function test_manager_can_adjust_stock(): void
    {
        $warehouse = Warehouse::create([
            'name' => 'Adjustment Warehouse',
            'city' => 'Wrocław',
            'address' => 'ul. Magazynowa 3',
            'is_active' => true,
        ]);

        $product = Product::create([
            'name' => 'Adjustable Product',
            'slug' => 'adjustable-product',
            'price' => 19.99,
        ]);

        $response = $this->actingAs($this->manager(), 'tenant')
            ->withoutTenantMiddleware()
            ->postJson(route('tenant.manager.warehouses.stock.adjust', $warehouse), [
                'product_id' => $product->id,
                'quantity' => 10,
                'reason' => 'Initial stock load',
            ]);

        $response->assertRedirect();
    }

    // ─── transferStock() ────────────────────────────────────────────────

    private function warehousePair(): array
    {
        return [
            Warehouse::create(['name' => 'A', 'city' => 'Warszawa', 'address' => 'ul. A 1', 'is_active' => true]),
            Warehouse::create(['name' => 'B', 'city' => 'Kraków', 'address' => 'ul. B 1', 'is_active' => true]),
        ];
    }

    public function test_manager_can_transfer_stock_between_warehouses(): void
    {
        [$from, $to] = $this->warehousePair();
        $product = Product::create(['name' => 'Transferowalny', 'slug' => 'transferowalny', 'price' => 10]);
        ProductWarehouseStock::create(['product_id' => $product->id, 'warehouse_id' => $from->id, 'quantity' => 50]);

        $response = $this->actingAs($this->manager(), 'tenant')
            ->withoutTenantMiddleware()
            ->postJson(route('tenant.manager.warehouses.stock.transfer', $from), [
                'to_warehouse_id' => $to->id,
                'product_id' => $product->id,
                'quantity' => 20,
            ]);

        $response->assertRedirect();
        $this->assertEquals(30, ProductWarehouseStock::where('product_id', $product->id)->where('warehouse_id', $from->id)->value('quantity'));
        $this->assertEquals(20, ProductWarehouseStock::where('product_id', $product->id)->where('warehouse_id', $to->id)->value('quantity'));
        $this->assertDatabaseHas('warehouse_transfers', [
            'product_id' => $product->id, 'from_warehouse_id' => $from->id,
            'to_warehouse_id' => $to->id, 'quantity' => 20,
        ]);
    }

    public function test_transfer_is_rejected_when_source_lacks_available_stock(): void
    {
        [$from, $to] = $this->warehousePair();
        $product = Product::create(['name' => 'Za malo', 'slug' => 'za-malo', 'price' => 10]);
        ProductWarehouseStock::create(['product_id' => $product->id, 'warehouse_id' => $from->id, 'quantity' => 30]);

        $response = $this->actingAs($this->manager(), 'tenant')
            ->withoutTenantMiddleware()
            ->postJson(route('tenant.manager.warehouses.stock.transfer', $from), [
                'to_warehouse_id' => $to->id,
                'product_id' => $product->id,
                'quantity' => 100,
            ]);

        $response->assertStatus(422);
        // Neither side moved — no phantom stock created out of the rejected transfer.
        $this->assertEquals(30, ProductWarehouseStock::where('product_id', $product->id)->where('warehouse_id', $from->id)->value('quantity'));
        $this->assertDatabaseMissing('product_warehouse_stock', ['product_id' => $product->id, 'warehouse_id' => $to->id]);
    }

    public function test_transfer_excludes_reserved_stock_from_available_quantity(): void
    {
        [$from, $to] = $this->warehousePair();
        $product = Product::create(['name' => 'Zarezerwowany', 'slug' => 'zarezerwowany', 'price' => 10]);
        ProductWarehouseStock::create([
            'product_id' => $product->id, 'warehouse_id' => $from->id,
            'quantity' => 30, 'reserved_quantity' => 25,
        ]);

        // Only 5 actually available (30 - 25 reserved).
        $response = $this->actingAs($this->manager(), 'tenant')
            ->withoutTenantMiddleware()
            ->postJson(route('tenant.manager.warehouses.stock.transfer', $from), [
                'to_warehouse_id' => $to->id,
                'product_id' => $product->id,
                'quantity' => 10,
            ]);

        $response->assertStatus(422);
    }

    public function test_transfer_rejects_same_source_and_destination(): void
    {
        [$from] = $this->warehousePair();
        $product = Product::create(['name' => 'Ten sam', 'slug' => 'ten-sam', 'price' => 10]);
        ProductWarehouseStock::create(['product_id' => $product->id, 'warehouse_id' => $from->id, 'quantity' => 10]);

        $response = $this->actingAs($this->manager(), 'tenant')
            ->withoutTenantMiddleware()
            ->postJson(route('tenant.manager.warehouses.stock.transfer', $from), [
                'to_warehouse_id' => $from->id,
                'product_id' => $product->id,
                'quantity' => 5,
            ]);

        $response->assertStatus(422);
    }
}
