<?php

namespace Tests\Feature;

use App\Mail\Tenant\CampaignMail;
use App\Models\Tenant\EmailCampaign;
use App\Models\Tenant\NewsletterSubscriber;
use App\Models\Tenant\User;
use Illuminate\Support\Facades\Mail;
use Tests\TenantTestCase;

/**
 * Round 21 finding: the newsletter table this test file exercises fed
 * nothing — `MarketingController::send()` only ever queried the Customer
 * table, so every storefront newsletter signup landed in a dead table no
 * admin page listed and no campaign ever reached. Fixed by adding an
 * explicit `target=newsletter` campaign option and its own unsubscribe
 * token (NewsletterSubscriber::unsubscribeToken(), same HMAC pattern as
 * Customer's), so the CTA that captures the email now actually leads
 * somewhere.
 */
class NewsletterTest extends TenantTestCase
{
    public function test_visitor_can_subscribe(): void
    {
        $response = $this->withoutTenantMiddleware()
            ->postJson(route('tenant.newsletter.subscribe'), ['email' => 'fan@example.com']);

        $response->assertRedirect();
        $this->assertDatabaseHas('newsletter_subscribers', ['email' => 'fan@example.com']);
    }

    public function test_subscribe_requires_valid_email(): void
    {
        $response = $this->withoutTenantMiddleware()
            ->postJson(route('tenant.newsletter.subscribe'), ['email' => 'not-an-email']);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['email']);
    }

    public function test_resubscribing_is_idempotent(): void
    {
        $this->withoutTenantMiddleware()
            ->postJson(route('tenant.newsletter.subscribe'), ['email' => 'fan@example.com']);
        $this->withoutTenantMiddleware()
            ->postJson(route('tenant.newsletter.subscribe'), ['email' => 'fan@example.com']);

        $this->assertDatabaseCount('newsletter_subscribers', 1);
    }

    public function test_honeypot_field_silently_blocks_subscription(): void
    {
        $response = $this->withoutTenantMiddleware()
            ->postJson(route('tenant.newsletter.subscribe'), [
                'email' => 'bot@example.com',
                'website' => 'http://spam.example',
            ]);

        $response->assertRedirect();
        $this->assertDatabaseMissing('newsletter_subscribers', ['email' => 'bot@example.com']);
    }

    public function test_newsletter_campaign_target_actually_emails_subscribers(): void
    {
        Mail::fake();

        NewsletterSubscriber::create(['email' => 'subscriber1@example.com']);
        NewsletterSubscriber::create(['email' => 'subscriber2@example.com']);
        NewsletterSubscriber::create(['email' => 'unsubscribed@example.com', 'unsubscribed_at' => now()]);

        $campaign = EmailCampaign::create([
            'name' => 'Newsletter Blast', 'subject' => 'Nowości', 'content' => 'Sprawdź nasze nowości!',
            'target' => 'newsletter', 'status' => 'draft',
        ]);

        $manager = User::create([
            'name' => 'Manager', 'email' => 'mgr-nl@test.com', 'password' => bcrypt('s'),
            'role' => 'manager', 'is_active' => true,
        ]);

        $response = $this->actingAs($manager, 'tenant')
            ->withoutTenantMiddleware()
            ->postJson(route('tenant.manager.marketing.send', $campaign));

        $response->assertRedirect();
        $campaign->refresh();

        $this->assertEquals('sent', $campaign->status);
        $this->assertEquals(2, $campaign->recipients_count);
        Mail::assertQueued(CampaignMail::class, fn ($mail) => $mail->recipientEmail === 'subscriber1@example.com');
        Mail::assertQueued(CampaignMail::class, fn ($mail) => $mail->recipientEmail === 'subscriber2@example.com');
        Mail::assertNotQueued(CampaignMail::class, fn ($mail) => $mail->recipientEmail === 'unsubscribed@example.com');
    }

    public function test_newsletter_subscriber_can_unsubscribe_with_valid_token(): void
    {
        $subscriber = NewsletterSubscriber::create(['email' => 'leaveme@example.com']);

        $response = $this->withoutTenantMiddleware()->get(route('tenant.marketing.unsubscribe', [
            'email' => $subscriber->email,
            'token' => $subscriber->unsubscribeToken(),
        ]));

        $response->assertOk();
        $this->assertNotNull($subscriber->fresh()->unsubscribed_at);
    }

    public function test_newsletter_unsubscribe_rejects_forged_token(): void
    {
        $subscriber = NewsletterSubscriber::create(['email' => 'stay@example.com']);

        $response = $this->withoutTenantMiddleware()->get(route('tenant.marketing.unsubscribe', [
            'email' => $subscriber->email,
            'token' => 'not-the-real-token',
        ]));

        $response->assertStatus(400);
        $this->assertNull($subscriber->fresh()->unsubscribed_at);
    }
}
