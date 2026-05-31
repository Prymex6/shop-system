<?php

namespace App\Jobs;

use App\Mail\Tenant\AbandonedCartMail;
use App\Models\Tenant\AbandonedCart;
use App\Models\Tenant\Customer;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendAbandonedCartReminder implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $backoff = 60;

    public function __construct(public AbandonedCart $cart) {}

    public function handle(): void
    {
        // Re-check: already sent or converted
        $cart = $this->cart->fresh();

        if (!$cart || $cart->reminder_sent_at || $cart->converted_at) {
            return;
        }

        if (!$cart->email) {
            return;
        }

        // Plan feature "abandoned_cart" (LandlordSeeder — off on Starter)
        // was never checked: a plan not paying for cart-recovery emails got
        // them sent anyway, for every abandoned cart, indefinitely. A lookup
        // failure never blocks the reminder — losing a real sale to a plan
        // check bug would be worse than one un-gated email.
        try {
            $plan = tenancy()->tenant?->plan;
            if ($plan && !$plan->hasFeature('abandoned_cart')) {
                return;
            }
        } catch (\Exception $e) {
            Log::warning('AbandonedCartReminder: plan feature check failed, sending anyway: ' . $e->getMessage());
        }

        // Unlike MarketingController::send() (bulk campaigns), this job had no
        // opt-out check at all — a customer who unsubscribed from marketing
        // still got cart-reminder emails. Check via the linked customer, or
        // (guest cart, no customer_id yet) by matching email against an
        // existing customer record.
        $customer = $cart->customer_id
            ? $cart->customer
            : Customer::where('email', $cart->email)->first();

        if ($customer?->marketing_opt_out) {
            return;
        }

        try {
            Mail::to($cart->email)->send(new AbandonedCartMail($cart));
            $cart->markReminderSent();

            Log::info('AbandonedCartReminder: reminder sent', [
                'cart_id' => $cart->id,
                'email' => $cart->email,
            ]);
        } catch (\Throwable $e) {
            Log::warning('AbandonedCartReminder: failed to send reminder', [
                'cart_id' => $cart->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }
}
