<?php

namespace Tests\Feature;

use App\Jobs\SendAbandonedCartReminder;
use App\Mail\Tenant\AbandonedCartMail;
use App\Models\Tenant\AbandonedCart;
use App\Models\Tenant\Customer;
use Illuminate\Support\Facades\Mail;
use Tests\TenantTestCase;

/**
 * Round 18 finding: cartTrackingStore.setEmail() existed but was never
 * called from anywhere, so guest carts (most TikTok-ad traffic is
 * anonymous) always tracked with email=null — the abandoned-cart reminder
 * feature was effectively non-functional for the exact audience it matters
 * most for. Fixed by calling setEmail()+track() on the checkout email
 * field's blur event (Checkout.vue, JS-only — this file covers the backend
 * contract that fix relies on: POST /api/cart/track already stores the
 * email correctly when sent).
 *
 * Round 20 finding: the reminder job never checked marketing_opt_out,
 * unlike MarketingController::send() (bulk campaigns) which does.
 */
class AbandonedCartEmailCaptureTest extends TenantTestCase
{
    public function test_cart_track_endpoint_stores_email_for_guest(): void
    {
        $response = $this->withoutTenantMiddleware()->postJson(route('tenant.cart.track'), [
            'session_id' => 'guest-session-1',
            'email' => 'guest@example.com',
            'items' => [['product_id' => 1, 'quantity' => 1, 'name' => 'Test', 'price' => 10]],
        ]);

        $response->assertOk();
        $this->assertDatabaseHas('abandoned_carts', [
            'session_id' => 'guest-session-1',
            'email' => 'guest@example.com',
            'customer_id' => null,
        ]);
    }

    /**
     * Round 21 finding: `/api/cart/track` is unauthenticated by necessity
     * (guest carts), which means `email` is fully attacker-supplied — and
     * unlike this feature's every other reminder-suppression check, nothing
     * verified the submitter actually controls that address before it feeds
     * SendAbandonedCartReminder, which mails it for real ~60 minutes later.
     * Contact/Newsletter/Chat (the app's other no-login public endpoints)
     * all had a honeypot guard against this exact class of abuse; this one
     * didn't. Fixed by adding the same ValidatesHoneypot check.
     */
    public function test_cart_track_endpoint_silently_ignores_honeypot_filled_submission(): void
    {
        $response = $this->withoutTenantMiddleware()->postJson(route('tenant.cart.track'), [
            'session_id' => 'bot-session-1',
            'email' => 'victim@example.com',
            'items' => [['product_id' => 1, 'quantity' => 1, 'name' => 'Test', 'price' => 10]],
            'website' => 'https://spam-bot.example',
        ]);

        $response->assertOk();
        $this->assertDatabaseMissing('abandoned_carts', [
            'session_id' => 'bot-session-1',
        ]);
    }

    public function test_reminder_is_not_sent_to_opted_out_customer(): void
    {
        Mail::fake();

        $customer = Customer::create([
            'name' => 'Opted Out', 'email' => 'optout@test.com', 'password' => bcrypt('secret'),
            'marketing_opt_out' => true,
        ]);

        $cart = AbandonedCart::create([
            'session_id' => 's-optout', 'customer_id' => $customer->id, 'email' => $customer->email,
            'cart_data' => ['items' => [['name' => 'X', 'quantity' => 1, 'price' => 10]]],
        ]);

        (new SendAbandonedCartReminder($cart))->handle();

        Mail::assertNothingSent();
        $this->assertNull($cart->fresh()->reminder_sent_at);
    }

    public function test_reminder_is_not_sent_to_guest_email_matching_opted_out_customer(): void
    {
        Mail::fake();

        Customer::create([
            'name' => 'Opted Out', 'email' => 'optout2@test.com', 'password' => bcrypt('secret'),
            'marketing_opt_out' => true,
        ]);

        // Guest cart, no customer_id — only linked by matching email.
        $cart = AbandonedCart::create([
            'session_id' => 's-guest-optout', 'email' => 'optout2@test.com',
            'cart_data' => ['items' => [['name' => 'X', 'quantity' => 1, 'price' => 10]]],
        ]);

        (new SendAbandonedCartReminder($cart))->handle();

        Mail::assertNothingSent();
    }

    public function test_reminder_is_sent_to_customer_without_opt_out(): void
    {
        Mail::fake();

        $customer = Customer::create([
            'name' => 'Subscribed', 'email' => 'subscribed@test.com', 'password' => bcrypt('secret'),
            'marketing_opt_out' => false,
        ]);

        $cart = AbandonedCart::create([
            'session_id' => 's-subscribed', 'customer_id' => $customer->id, 'email' => $customer->email,
            'cart_data' => ['items' => [['name' => 'X', 'quantity' => 1, 'price' => 10]]],
        ]);

        (new SendAbandonedCartReminder($cart))->handle();

        Mail::assertSent(AbandonedCartMail::class);
        $this->assertNotNull($cart->fresh()->reminder_sent_at);
    }

    public function test_reminder_is_sent_to_pure_guest_with_no_matching_customer(): void
    {
        Mail::fake();

        $cart = AbandonedCart::create([
            'session_id' => 's-pure-guest', 'email' => 'neverregistered@test.com',
            'cart_data' => ['items' => [['name' => 'X', 'quantity' => 1, 'price' => 10]]],
        ]);

        (new SendAbandonedCartReminder($cart))->handle();

        Mail::assertSent(AbandonedCartMail::class);
    }
}
