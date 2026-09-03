<?php

namespace Tests\Feature\Auth;

use App\Models\Tenant\Customer;
use App\Models\Tenant\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Tests\TenantTestCase;

/**
 * Tests for password reset flow — staff (tenant guard) and customers (customer guard).
 *
 * Both use the password_reset_tokens table in the tenant DB (included in
 * tenant migrations) with separate brokers: 'tenant_users' and 'customers'.
 */
class PasswordResetTest extends TenantTestCase
{
    // ─── STAFF (tenant guard / broker: tenant_users) ───────────────────────

    public function test_staff_forgot_password_page_loads(): void
    {
        $response = $this->withoutTenantMiddleware()
            ->get(route('tenant.password.request'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('Tenant/Auth/ForgotPassword')
        );
    }

    public function test_staff_can_request_password_reset_link(): void
    {
        $user = User::create([
            'name' => 'Pracownik',
            'email' => 'staff@test.com',
            'password' => bcrypt('password'),
            'role' => 'waiter',
            'is_active' => true,
        ]);

        $response = $this->withoutTenantMiddleware()
            ->post(route('tenant.password.email'), ['email' => $user->email]);

        $response->assertRedirect();
        // Verify the reset token was stored in DB (broker issued a reset link)
        $this->assertDatabaseHas('password_reset_tokens', ['email' => 'staff@test.com']);
    }

    public function test_staff_reset_link_for_unknown_email_does_not_expose_user_existence(): void
    {
        // Previously INVALID_USER surfaced as a distinct form validation
        // error ("Nie znaleziono użytkownika..."), letting an attacker
        // enumerate which staff accounts exist just by reading the
        // response — no timing analysis needed.
        $response = $this->withoutTenantMiddleware()
            ->post(route('tenant.password.email'), ['email' => 'nobody@test.com']);

        $response->assertRedirect();
        $response->assertSessionHasNoErrors();
        $response->assertSessionHas('success');
    }

    public function test_staff_reset_response_is_identical_for_known_and_unknown_email(): void
    {
        User::create([
            'name' => 'Pracownik', 'email' => 'realstaff@test.com',
            'password' => bcrypt('password'), 'role' => 'waiter', 'is_active' => true,
        ]);

        $known = $this->withoutTenantMiddleware()
            ->post(route('tenant.password.email'), ['email' => 'realstaff@test.com']);
        $knownMessage = session()->get('success');

        $unknown = $this->withoutTenantMiddleware()
            ->post(route('tenant.password.email'), ['email' => 'nosuchstaff@test.com']);
        $unknownMessage = session()->get('success');

        $known->assertSessionHasNoErrors();
        $unknown->assertSessionHasNoErrors();
        $this->assertNotNull($knownMessage);
        $this->assertSame($knownMessage, $unknownMessage, 'Response message must not reveal whether the account exists');
    }

    public function test_staff_reset_link_requires_valid_email(): void
    {
        $response = $this->withoutTenantMiddleware()
            ->postJson(route('tenant.password.email'), ['email' => 'not-an-email']);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['email']);
    }

    public function test_staff_reset_password_form_loads(): void
    {
        $response = $this->withoutTenantMiddleware()
            ->get(route('tenant.password.reset', 'dummy-token') . '?email=staff@test.com');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('Tenant/Auth/ResetPassword')
        );
    }

    public function test_staff_can_reset_password_with_valid_token(): void
    {
        $user = User::create([
            'name' => 'Pracownik',
            'email' => 'staff@test.com',
            'password' => bcrypt('oldpassword'),
            'role' => 'waiter',
            'is_active' => true,
        ]);

        $token = Password::broker('tenant_users')->createToken($user);

        $response = $this->withoutTenantMiddleware()
            ->postJson(route('tenant.password.update'), [
                'token' => $token,
                'email' => $user->email,
                'password' => 'newpassword123',
                'password_confirmation' => 'newpassword123',
            ]);

        $response->assertRedirect();
        $user->refresh();
        $this->assertTrue(Hash::check('newpassword123', $user->password));
    }

    public function test_staff_reset_fails_with_invalid_token(): void
    {
        User::create([
            'name' => 'Pracownik',
            'email' => 'staff@test.com',
            'password' => bcrypt('password'),
            'role' => 'waiter',
            'is_active' => true,
        ]);

        // Controller uses back()->withErrors() for invalid tokens → 302 redirect
        $response = $this->withoutTenantMiddleware()
            ->post(route('tenant.password.update'), [
                'token' => 'completely-wrong-token',
                'email' => 'staff@test.com',
                'password' => 'newpassword123',
                'password_confirmation' => 'newpassword123',
            ]);

        $response->assertRedirect();
        $response->assertSessionHasErrors();
    }

    public function test_staff_reset_validates_password_confirmation(): void
    {
        $response = $this->withoutTenantMiddleware()
            ->postJson(route('tenant.password.update'), [
                'token' => 'some-token',
                'email' => 'staff@test.com',
                'password' => 'newpassword123',
                'password_confirmation' => 'mismatch456',
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['password']);
    }

    public function test_staff_reset_requires_minimum_password_length(): void
    {
        $response = $this->withoutTenantMiddleware()
            ->postJson(route('tenant.password.update'), [
                'token' => 'some-token',
                'email' => 'staff@test.com',
                'password' => 'short',
                'password_confirmation' => 'short',
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['password']);
    }

    // ─── CUSTOMER (customer guard / broker: customers) ─────────────────────

    public function test_customer_forgot_password_page_loads(): void
    {
        $response = $this->withoutTenantMiddleware()
            ->get(route('tenant.client.password.request'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('Tenant/Client/Auth/ForgotPassword')
        );
    }

    public function test_customer_can_request_password_reset_link(): void
    {
        $customer = Customer::create([
            'name' => 'Klient',
            'email' => 'customer@test.com',
            'password' => bcrypt('password'),
            'phone' => '123456789',
        ]);

        $response = $this->withoutTenantMiddleware()
            ->post(route('tenant.client.password.email'), ['email' => $customer->email]);

        $response->assertRedirect();
        // Verify the reset token was stored in DB (broker issued a reset link)
        $this->assertDatabaseHas('password_reset_tokens', ['email' => 'customer@test.com']);
    }

    public function test_customer_reset_response_is_identical_for_known_and_unknown_email(): void
    {
        Customer::create([
            'name' => 'Klient', 'email' => 'realcustomer@test.com',
            'password' => bcrypt('password'), 'phone' => '123456789',
        ]);

        $known = $this->withoutTenantMiddleware()
            ->post(route('tenant.client.password.email'), ['email' => 'realcustomer@test.com']);
        $knownMessage = session()->get('success');

        $unknown = $this->withoutTenantMiddleware()
            ->post(route('tenant.client.password.email'), ['email' => 'nosuchcustomer@test.com']);
        $unknownMessage = session()->get('success');

        $known->assertSessionHasNoErrors();
        $unknown->assertSessionHasNoErrors();
        $this->assertNotNull($knownMessage);
        $this->assertSame($knownMessage, $unknownMessage, 'Response message must not reveal whether the account exists');
    }

    public function test_customer_reset_password_form_loads(): void
    {
        $response = $this->withoutTenantMiddleware()
            ->get(route('tenant.client.password.reset', 'dummy-token') . '?email=customer@test.com');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('Tenant/Client/Auth/ResetPassword')
        );
    }

    public function test_customer_can_reset_password_with_valid_token(): void
    {
        $customer = Customer::create([
            'name' => 'Klient',
            'email' => 'customer@test.com',
            'password' => bcrypt('oldpassword'),
            'phone' => '123456789',
        ]);

        $token = Password::broker('customers')->createToken($customer);

        $response = $this->withoutTenantMiddleware()
            ->postJson(route('tenant.client.password.update'), [
                'token' => $token,
                'email' => $customer->email,
                'password' => 'newpassword123',
                'password_confirmation' => 'newpassword123',
            ]);

        $response->assertRedirect();
        $customer->refresh();
        $this->assertTrue(Hash::check('newpassword123', $customer->password));
    }

    public function test_customer_reset_fails_with_invalid_token(): void
    {
        Customer::create([
            'name' => 'Klient',
            'email' => 'customer@test.com',
            'password' => bcrypt('password'),
            'phone' => '123456789',
        ]);

        $response = $this->withoutTenantMiddleware()
            ->post(route('tenant.client.password.update'), [
                'token' => 'completely-wrong-token',
                'email' => 'customer@test.com',
                'password' => 'newpassword123',
                'password_confirmation' => 'newpassword123',
            ]);

        $response->assertRedirect();
        $response->assertSessionHasErrors();
    }

    public function test_customer_reset_validates_password_confirmation(): void
    {
        $response = $this->withoutTenantMiddleware()
            ->postJson(route('tenant.client.password.update'), [
                'token' => 'some-token',
                'email' => 'customer@test.com',
                'password' => 'newpassword123',
                'password_confirmation' => 'mismatch456',
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['password']);
    }
}
