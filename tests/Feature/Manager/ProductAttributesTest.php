<?php

namespace Tests\Feature\Manager;

use App\Models\Tenant\ProductAttribute;
use App\Models\Tenant\User;
use Tests\TenantTestCase;

/**
 * ProductController::attributes()/storeAttribute()/etc. were the only
 * endpoints in the app that create ProductAttribute/ProductAttributeValue
 * rows, but they all pointed at a missing Attributes.vue — a fresh tenant
 * had no way in the UI to ever create an attribute, silently disabling
 * variant creation (Variants.vue only reads existing attributes into a
 * dropdown). Built the missing page; these tests cover the backend
 * endpoints it drives.
 */
class ProductAttributesTest extends TenantTestCase
{
    private function manager(): User
    {
        return User::create([
            'name' => 'Manager', 'email' => 'manager@test.com',
            'password' => bcrypt('secret'), 'role' => 'manager', 'is_active' => true,
        ]);
    }

    public function test_manager_can_view_attributes_page(): void
    {
        $response = $this->actingAs($this->manager(), 'tenant')
            ->withoutTenantMiddleware()
            ->get(route('tenant.manager.attributes.index'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('Tenant/Manager/Products/Attributes')->has('attributes')
        );
    }

    public function test_manager_can_create_attribute(): void
    {
        $response = $this->actingAs($this->manager(), 'tenant')
            ->withoutTenantMiddleware()
            ->postJson(route('tenant.manager.attributes.store'), ['name' => 'Kolor']);

        $response->assertOk();
        $this->assertDatabaseHas('product_attributes', ['name' => 'Kolor']);
    }

    public function test_manager_can_add_value_to_attribute(): void
    {
        $attr = ProductAttribute::create(['name' => 'Kolor']);

        $response = $this->actingAs($this->manager(), 'tenant')
            ->withoutTenantMiddleware()
            ->postJson(route('tenant.manager.attributes.values.store', $attr), [
                'value' => 'Czerwony',
                'color_hex' => '#ff0000',
            ]);

        $response->assertOk();
        $this->assertDatabaseHas('product_attribute_values', [
            'attribute_id' => $attr->id,
            'value' => 'Czerwony',
            'color_hex' => '#ff0000',
        ]);
    }

    public function test_manager_can_delete_attribute_value(): void
    {
        $attr = ProductAttribute::create(['name' => 'Kolor']);
        $value = $attr->values()->create(['value' => 'Czerwony']);

        $this->actingAs($this->manager(), 'tenant')
            ->withoutTenantMiddleware()
            ->deleteJson(route('tenant.manager.attributes.values.destroy', [$attr, $value]))
            ->assertOk();

        $this->assertDatabaseMissing('product_attribute_values', ['id' => $value->id]);
    }

    public function test_manager_can_delete_attribute(): void
    {
        $attr = ProductAttribute::create(['name' => 'Kolor']);

        $this->actingAs($this->manager(), 'tenant')
            ->withoutTenantMiddleware()
            ->deleteJson(route('tenant.manager.attributes.destroy', $attr))
            ->assertOk();

        $this->assertDatabaseMissing('product_attributes', ['id' => $attr->id]);
    }
}
