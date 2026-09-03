<?php

namespace Tests\Feature\Auth;

use App\Models\Tenant\Customer;
use Tests\TenantTestCase;

/**
 * Tests for customer authentication (customer guard).
 */
class CustomerAuthTest extends TenantTestCase
{
    private function makeCustomer(string $email = 'jan@test.com', string $password = 'secret123'): Customer
    {
        return Customer::create([
            'name' => 'Jan Testowy',
            'email' => $email,
            'password' => bcrypt($password),
            'phone' => '123456789',
        ]);
    }

    // ─── Register ─────────────────────────────────────────────────────

    public function test_registration_page_loads(): void
    {
        $response = $this->withoutTenantMiddleware()->get(route('tenant.client.register'));
        $response->assertStatus(200);
    }

    public function test_customer_can_register(): void
    {
        $response = $this->withoutTenantMiddleware()
            ->postJson(route('tenant.client.register'), [
                'name' => 'Nowy Klient',
                'email' => 'nowy@test.com',
                'password' => 'haslo1234',
                'password_confirmation' => 'haslo1234',
                'phone' => '987654321',
                'terms_accepted' => true,
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('customers', ['email' => 'nowy@test.com']);
    }

    public function test_registration_requires_unique_email(): void
    {
        $this->makeCustomer('existing@test.com');

        $response = $this->withoutTenantMiddleware()
            ->postJson(route('tenant.client.register'), [
                'name' => 'Duplikat',
                'email' => 'existing@test.com',
                'password' => 'haslo1234',
                'password_confirmation' => 'haslo1234',
                'phone' => '111222333',
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['email']);
    }

    public function test_registration_requires_valid_email(): void
    {
        $response = $this->withoutTenantMiddleware()
            ->postJson(route('tenant.client.register'), [
                'name' => 'Test',
                'email' => 'not-an-email',
                'password' => 'haslo1234',
                'password_confirmation' => 'haslo1234',
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['email']);
    }

    public function test_registration_requires_password_confirmation(): void
    {
        $response = $this->withoutTenantMiddleware()
            ->postJson(route('tenant.client.register'), [
                'name' => 'Test',
                'email' => 'new@test.com',
                'password' => 'haslo1234',
                'password_confirmation' => 'inne-haslo',
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['password']);
    }

    // ─── Login ────────────────────────────────────────────────────────

    public function test_login_page_loads(): void
    {
        $response = $this->withoutTenantMiddleware()->get(route('tenant.client.login'));
        $response->assertStatus(200);
    }

    public function test_customer_can_login_with_valid_credentials(): void
    {
        $this->makeCustomer('jan@test.com', 'secret123');

        $response = $this->withoutTenantMiddleware()
            ->postJson(route('tenant.client.login'), [
                'email' => 'jan@test.com',
                'password' => 'secret123',
            ]);

        $response->assertRedirect();
        $this->assertAuthenticated('customer');
    }

    public function test_customer_login_fails_with_wrong_password(): void
    {
        $this->makeCustomer('jan@test.com', 'correct');

        $response = $this->withoutTenantMiddleware()
            ->postJson(route('tenant.client.login'), [
                'email' => 'jan@test.com',
                'password' => 'wrong',
            ]);

        $response->assertStatus(422);
        $this->assertGuest('customer');
    }

    // ─── Logout ───────────────────────────────────────────────────────

    public function test_customer_can_logout(): void
    {
        $customer = $this->makeCustomer();

        $response = $this->actingAs($customer, 'customer')
            ->withoutTenantMiddleware()
            ->post(route('tenant.client.logout'));

        $response->assertRedirect();
        $this->assertGuest('customer');
    }

    // ─── Referral code ────────────────────────────────────────────────

    // S4.1.4 – Registration with valid referral code links account to referrer
    public function test_registration_with_referral_code_links_to_referrer(): void
    {
        $referrer = $this->makeCustomer('referrer@test.com');
        // Ensure referrer has a referral_code
        $referrer->referral_code = 'ABCD1234';
        $referrer->save();

        $response = $this->withoutTenantMiddleware()
            ->postJson(route('tenant.client.register'), [
                'name' => 'Nowy Klient',
                'email' => 'nowy@test.com',
                'password' => 'haslo1234',
                'password_confirmation' => 'haslo1234',
                'phone' => '987654321',
                'terms_accepted' => true,
                'referral_code' => 'ABCD1234',
            ]);

        $response->assertRedirect();
        $newCustomer = Customer::where('email', 'nowy@test.com')->first();
        $this->assertNotNull($newCustomer);
        $this->assertEquals($referrer->id, $newCustomer->loyalty_referred_by);
    }

    // S4.1.5 – Registration without referral code works normally
    public function test_registration_without_referral_code_works(): void
    {
        $response = $this->withoutTenantMiddleware()
            ->postJson(route('tenant.client.register'), [
                'name' => 'Normalny Klient',
                'email' => 'normalny@test.com',
                'password' => 'haslo1234',
                'password_confirmation' => 'haslo1234',
                'phone' => '111222333',
                'terms_accepted' => true,
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('customers', [
            'email' => 'normalny@test.com',
            'loyalty_referred_by' => null,
        ]);
    }

    // Invalid referral code is silently ignored
    public function test_invalid_referral_code_is_ignored(): void
    {
        $response = $this->withoutTenantMiddleware()
            ->postJson(route('tenant.client.register'), [
                'name' => 'Klient Z Kodem',
                'email' => 'kodem@test.com',
                'password' => 'haslo1234',
                'password_confirmation' => 'haslo1234',
                'phone' => '999888777',
                'terms_accepted' => true,
                'referral_code' => 'ZLYCODE',
            ]);

        // Should still register successfully (invalid code ignored)
        $response->assertRedirect();
        $this->assertDatabaseHas('customers', ['email' => 'kodem@test.com']);
    }

    // ─── Account page requires auth ────────────────────────────────────

    public function test_account_page_requires_customer_auth(): void
    {
        $response = $this->withoutTenantMiddleware()->get(route('tenant.account'));
        // Unauthenticated customer should be redirected to login
        $response->assertRedirect();
    }

    public function test_authenticated_customer_can_view_account(): void
    {
        $customer = $this->makeCustomer();

        $response = $this->actingAs($customer, 'customer')
            ->withoutTenantMiddleware()
            ->get(route('tenant.account'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('Tenant/Client/Account')
        );
    }
}
