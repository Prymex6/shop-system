<?php

namespace Tests\Feature\Client;

use App\Mail\Tenant\CustomerWelcomeMail;
use App\Models\Tenant\Customer;
use Illuminate\Support\Facades\Mail;
use Tests\TenantTestCase;

class CustomerWelcomeMailTest extends TenantTestCase
{
    /** Signing up sends the welcome email. */
    public function test_registration_queues_welcome_mail(): void
    {
        Mail::fake();
        $this->setSetting('shop_name', 'Pizza Testowa');

        $response = $this->withoutTenantMiddleware()->post('/konto/rejestracja', [
            'name' => 'Nowy Klient',
            'email' => 'nowy@klient.pl',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'phone' => '123456789',
            'terms_accepted' => true,
        ]);

        Mail::assertQueued(CustomerWelcomeMail::class, function ($mail) {
            return $mail->hasTo('nowy@klient.pl');
        });
    }

    /** Signing up leaves a customer in the database. */
    public function test_registration_creates_customer_in_database(): void
    {
        Mail::fake();

        $this->withoutTenantMiddleware()->post('/konto/rejestracja', [
            'name' => 'Jan Nowak',
            'email' => 'jan@nowak.pl',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'phone' => '100200300',
            'terms_accepted' => true,
        ]);

        $this->assertDatabaseHas('customers', ['email' => 'jan@nowak.pl']);
    }

    /** The welcome email carries the customer name and the shop name. */
    public function test_welcome_mail_contains_customer_name_and_restaurant(): void
    {
        $this->setSetting('shop_name', 'Pizza Roma');

        $mail = new CustomerWelcomeMail(
            customerName: 'Tomasz Kowalski',
            shopName: 'Pizza Roma',
        );

        $rendered = $mail->render();

        $this->assertStringContainsString('Tomasz Kowalski', $rendered);
        $this->assertStringContainsString('Pizza Roma', $rendered);
    }

    /** An address already in use does not make a second account. */
    public function test_registration_rejects_duplicate_email(): void
    {
        Mail::fake();

        Customer::create([
            'name' => 'Istniejący',
            'email' => 'istniejacy@test.pl',
            'password' => bcrypt('haslo'),
            'phone' => '111222333',
        ]);

        $response = $this->withoutTenantMiddleware()->post('/konto/rejestracja', [
            'name' => 'Nowy Klient',
            'email' => 'istniejacy@test.pl',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'phone' => '999888777',
        ]);

        $response->assertSessionHasErrors('email');
        Mail::assertNothingQueued();
    }
}
