<?php

namespace Tests\Feature\Client;

use App\Models\Tenant\Customer;
use App\Models\Tenant\Product;
use Tests\TenantTestCase;

/**
 * Tests for customer wishlist functionality.
 */
class WishlistTest extends TenantTestCase
{
    private function customer(): Customer
    {
        return Customer::create([
            'name' => 'Klient',
            'email' => 'klient@test.com',
            'password' => bcrypt('s'),
        ]);
    }

    private function product(): Product
    {
        return Product::create([
            'name' => 'Produkt Testowy',
            'slug' => 'produkt-testowy',
            'price' => 9.99,
            'type' => 'physical',
            'status' => 'active',
        ]);
    }

    public function test_guest_cannot_view_wishlist(): void
    {
        $response = $this->withoutTenantMiddleware()
            ->get(route('tenant.wishlist'));

        $response->assertStatus(302);
    }

    public function test_customer_can_view_wishlist(): void
    {
        $response = $this->actingAs($this->customer(), 'customer')
            ->withoutTenantMiddleware()
            ->get(route('tenant.wishlist'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('Tenant/Client/Wishlist')
            ->has('items')
        );
    }

    public function test_customer_can_add_product_to_wishlist(): void
    {
        $customer = $this->customer();
        $product = $this->product();

        $response = $this->actingAs($customer, 'customer')
            ->withoutTenantMiddleware()
            ->post(route('tenant.wishlist.toggle', $product));

        $response->assertRedirect();
    }

    public function test_customer_can_remove_product_from_wishlist(): void
    {
        $customer = $this->customer();
        $product = $this->product();

        // Add first
        $this->actingAs($customer, 'customer')
            ->withoutTenantMiddleware()
            ->post(route('tenant.wishlist.toggle', $product));

        // Toggle again to remove
        $response = $this->actingAs($customer, 'customer')
            ->withoutTenantMiddleware()
            ->post(route('tenant.wishlist.toggle', $product));

        $response->assertRedirect();
    }
}
