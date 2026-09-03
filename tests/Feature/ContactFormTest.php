<?php

namespace Tests\Feature;

use App\Mail\Tenant\ContactInquiryManagerMail;
use App\Models\Tenant\Setting;
use Illuminate\Support\Facades\Mail;
use Tests\TenantTestCase;

class ContactFormTest extends TenantTestCase
{
    public function test_visitor_can_send_contact_message(): void
    {
        Mail::fake();
        Setting::set('shop_email', 'sklep@example.com');

        $response = $this->withoutTenantMiddleware()
            ->postJson(route('tenant.contact.send'), [
                'name' => 'Jan Kowalski',
                'email' => 'jan@example.com',
                'message' => 'Mam pytanie o produkt.',
            ]);

        $response->assertOk();
        $this->assertDatabaseHas('contact_inquiries', [
            'name' => 'Jan Kowalski',
            'email' => 'jan@example.com',
        ]);
        Mail::assertQueued(ContactInquiryManagerMail::class, fn ($mail) => $mail->hasTo('sklep@example.com'));
    }

    public function test_message_is_saved_even_without_shop_email_configured(): void
    {
        Mail::fake();

        $response = $this->withoutTenantMiddleware()
            ->postJson(route('tenant.contact.send'), [
                'name' => 'Jan Kowalski',
                'email' => 'jan@example.com',
                'message' => 'Mam pytanie o produkt.',
            ]);

        $response->assertOk();
        $this->assertDatabaseHas('contact_inquiries', ['email' => 'jan@example.com']);
        Mail::assertNothingQueued();
    }

    public function test_contact_form_requires_fields(): void
    {
        $response = $this->withoutTenantMiddleware()
            ->postJson(route('tenant.contact.send'), []);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['name', 'email', 'message']);
    }

    public function test_honeypot_field_silently_blocks_message(): void
    {
        Mail::fake();
        Setting::set('shop_email', 'sklep@example.com');

        $response = $this->withoutTenantMiddleware()
            ->postJson(route('tenant.contact.send'), [
                'name' => 'Bot',
                'email' => 'bot@example.com',
                'message' => 'spam spam spam',
                'website' => 'http://spam.example',
            ]);

        $response->assertOk();
        $this->assertDatabaseMissing('contact_inquiries', ['email' => 'bot@example.com']);
        Mail::assertNothingQueued();
    }
}
