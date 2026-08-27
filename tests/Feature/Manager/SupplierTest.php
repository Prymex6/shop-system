<?php

namespace Tests\Feature\Manager;

use App\Models\Tenant\Supplier;
use App\Models\Tenant\User;
use Tests\TenantTestCase;

/**
 * Tests for supplier management (CRUD).
 */
class SupplierTest extends TenantTestCase
{
    private function manager(): User
    {
        return User::create([
            'name' => 'Manager', 'email' => 'mgr@test.com',
            'password' => bcrypt('s'), 'role' => 'manager', 'is_active' => true,
        ]);
    }

    public function test_manager_can_view_suppliers(): void
    {
        $response = $this->actingAs($this->manager(), 'tenant')
            ->withoutTenantMiddleware()
            ->get(route('tenant.manager.suppliers.index'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('Tenant/Manager/Suppliers/Index')
            ->has('suppliers')
        );
    }

    public function test_manager_can_create_supplier(): void
    {
        $response = $this->actingAs($this->manager(), 'tenant')
            ->withoutTenantMiddleware()
            ->postJson(route('tenant.manager.suppliers.store'), [
                'name' => 'Acme Sp. z o.o.',
                'email' => 'kontakt@acme.pl',
                'phone' => '600700800',
                'contact_person' => 'Jan Kowalski',
                'is_active' => true,
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('suppliers', ['name' => 'Acme Sp. z o.o.', 'email' => 'kontakt@acme.pl']);
    }

    public function test_supplier_requires_name(): void
    {
        $response = $this->actingAs($this->manager(), 'tenant')
            ->withoutTenantMiddleware()
            ->postJson(route('tenant.manager.suppliers.store'), [
                'email' => 'no-name@test.com',
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['name']);
    }

    public function test_supplier_email_must_be_valid(): void
    {
        $response = $this->actingAs($this->manager(), 'tenant')
            ->withoutTenantMiddleware()
            ->postJson(route('tenant.manager.suppliers.store'), [
                'name' => 'Zły Email',
                'email' => 'not-an-email',
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['email']);
    }

    public function test_manager_can_update_supplier(): void
    {
        $supplier = Supplier::create([
            'name' => 'Stara Nazwa',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->manager(), 'tenant')
            ->withoutTenantMiddleware()
            ->putJson(route('tenant.manager.suppliers.update', $supplier), [
                'name' => 'Nowa Nazwa',
                'is_active' => true,
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('suppliers', ['id' => $supplier->id, 'name' => 'Nowa Nazwa']);
    }

    public function test_manager_can_delete_supplier(): void
    {
        $supplier = Supplier::create(['name' => 'Do Usunięcia', 'is_active' => true]);

        $response = $this->actingAs($this->manager(), 'tenant')
            ->withoutTenantMiddleware()
            ->deleteJson(route('tenant.manager.suppliers.destroy', $supplier));

        $response->assertRedirect();
        $this->assertDatabaseMissing('suppliers', ['id' => $supplier->id]);
    }
}
