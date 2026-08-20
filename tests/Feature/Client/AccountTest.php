<?php

namespace Tests\Feature\Client;

use App\Mail\Tenant\EmailChangeMail;
use App\Models\Tenant\Customer;
use Illuminate\Support\Facades\Mail;
use Tests\TenantTestCase;

/**
 * Tests for customer account management (profile update, password, GDPR delete).
 */
class AccountTest extends TenantTestCase
{
    private function customer(array $overrides = []): Customer
    {
        return Customer::create(array_merge([
            'name' => 'Jan Kowalski',
            'email' => 'jan@test.com',
            'password' => bcrypt('secret123'),
            'phone' => '123456789',
        ], $overrides));
    }

    public function test_customer_can_view_account(): void
    {
        $customer = $this->customer();

        $response = $this->actingAs($customer, 'customer')
            ->withoutTenantMiddleware()
            ->get(route('tenant.account'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('Tenant/Client/Account')
            ->has('customer')
            ->has('orders')
        );
    }

    public function test_customer_can_update_profile(): void
    {
        $customer = $this->customer();

        $response = $this->actingAs($customer, 'customer')
            ->withoutTenantMiddleware()
            ->putJson(route('tenant.account.update'), [
                'name' => 'Jan Zmieniony',
                'email' => 'jan@test.com',
                'phone' => '987654321',
            ]);

        $response->assertRedirect();
        $customer->refresh();
        $this->assertEquals('Jan Zmieniony', $customer->name);
        $this->assertEquals('987654321', $customer->phone);
    }

    public function test_changing_email_sends_branded_verification_mail(): void
    {
        Mail::fake();
        $customer = $this->customer();

        $this->actingAs($customer, 'customer')
            ->withoutTenantMiddleware()
            ->putJson(route('tenant.account.update'), [
                'name' => $customer->name,
                'email' => 'nowy@test.com',
            ]);

        Mail::assertQueued(EmailChangeMail::class, function ($mail) {
            return $mail->hasTo('nowy@test.com');
        });

        $customer->refresh();
        $this->assertEquals('jan@test.com', $customer->email);
        $this->assertEquals('nowy@test.com', $customer->pending_email);
    }

    public function test_profile_update_requires_name_and_email(): void
    {
        $customer = $this->customer();

        $response = $this->actingAs($customer, 'customer')
            ->withoutTenantMiddleware()
            ->putJson(route('tenant.account.update'), []);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['name', 'email']);
    }

    public function test_customer_can_change_password(): void
    {
        $customer = $this->customer();

        $response = $this->actingAs($customer, 'customer')
            ->withoutTenantMiddleware()
            ->putJson(route('tenant.account.password'), [
                'current_password' => 'secret123',
                'password' => 'newPassword456',
                'password_confirmation' => 'newPassword456',
            ]);

        $response->assertRedirect();
    }

    public function test_password_change_requires_correct_current_password(): void
    {
        $customer = $this->customer();

        $response = $this->actingAs($customer, 'customer')
            ->withoutTenantMiddleware()
            ->putJson(route('tenant.account.password'), [
                'current_password' => 'wrong_password',
                'password' => 'newPassword456',
                'password_confirmation' => 'newPassword456',
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['current_password']);
    }

    public function test_customer_can_delete_account_gdpr(): void
    {
        $customer = $this->customer();
        $order = $this->createTestOrder([
            'customer_id' => $customer->id,
            'customer_email' => 'jan@test.com',
            'customer_name' => 'Jan Kowalski',
        ]);

        $response = $this->actingAs($customer, 'customer')
            ->withoutTenantMiddleware()
            ->deleteJson(route('tenant.account.destroy'), [
                'password' => 'secret123',
            ]);

        $response->assertRedirect();

        // Customer should be deleted
        $this->assertDatabaseMissing('customers', ['id' => $customer->id, 'email' => 'jan@test.com']);

        // Orders should be anonymized
        $order->refresh();
        $this->assertNull($order->customer_email);
    }

    public function test_account_delete_requires_correct_password(): void
    {
        $customer = $this->customer();

        $response = $this->actingAs($customer, 'customer')
            ->withoutTenantMiddleware()
            ->deleteJson(route('tenant.account.destroy'), [
                'password' => 'wrong_password',
            ]);

        $response->assertStatus(422);
        $this->assertDatabaseHas('customers', ['id' => $customer->id]);
    }

    // S6.1.5 – Referral code is visible on account page
    public function test_account_page_includes_referral_code(): void
    {
        $customer = $this->customer();
        // Simulate auto-generated referral code (set manually for test)
        $customer->referral_code = 'MYCODE01';
        $customer->save();

        $response = $this->actingAs($customer, 'customer')
            ->withoutTenantMiddleware()
            ->get(route('tenant.account'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('Tenant/Client/Account')
            ->where('customer.referral_code', 'MYCODE01')
        );
    }

    // New customers get a referral code auto-generated
    public function test_new_customer_gets_referral_code(): void
    {
        $customer = Customer::create([
            'name' => 'Referral Test',
            'email' => 'reftest@test.com',
            'password' => bcrypt('secret'),
            'phone' => '500600700',
        ]);

        $customer->refresh();
        $this->assertNotNull($customer->referral_code);
        $this->assertEquals(8, strlen($customer->referral_code));
    }
}
