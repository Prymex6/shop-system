<?php

namespace App\Listeners;

use App\Events\OrderCreated;
use App\Models\Tenant\PushSubscription;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Minishlink\WebPush\Subscription;
use Minishlink\WebPush\WebPush;
use Throwable;

/**
 * Tell subscribed staff that an order has come in.
 *
 * This used to encrypt the payload itself: an ECDH exchange, an HKDF, and a
 * hand-built ES256 JWT, about a hundred and twenty lines of it. None of it
 * worked. openssl_dh_compute_key was called with three arguments where it
 * takes two, and the shared secret it was meant to produce was read before it
 * was ever assigned — so every payload was encrypted against null. The JWT was
 * signed with openssl_sign, which returns DER, where the VAPID spec asks for
 * the raw r‖s pair. Nothing said so: each failed push was caught, logged as a
 * warning, and the next one tried.
 *
 * It now goes through minishlink/web-push, which is the library everyone else
 * uses for this and is maintained by people who read the spec.
 */
class SendPushNotificationOnOrder implements ShouldQueue
{
    use InteractsWithQueue;

    public function handle(OrderCreated $event): void
    {
        $auth = $this->vapid();

        if ($auth === null) {
            return;
        }

        $subscriptions = PushSubscription::all();

        if ($subscriptions->isEmpty()) {
            return;
        }

        $order = $event->order;

        // Web Push shows this natively on a lock screen, readable without
        // unlocking, on every subscribed device whatever the person's role.
        // The order number alone is enough to act on; the customer's name and
        // the total are not anyone's business who happens to see the phone.
        $payload = json_encode([
            'title' => __('push.new_order', ['number' => $order->order_number]),
            'body' => __('push.new_order_body'),
            'icon' => '/pwa-192x192.png',
            'badge' => '/pwa-192x192.png',
            'tag' => 'new-order',
            'data' => ['url' => '/manager/orders'],
        ], JSON_THROW_ON_ERROR);

        try {
            $webPush = new WebPush(['VAPID' => $auth]);
        } catch (Throwable $error) {
            // Malformed VAPID keys are rejected here, before anything is sent.
            Log::warning('Web Push not configured correctly', ['error' => $error->getMessage()]);

            return;
        }

        foreach ($subscriptions as $subscription) {
            try {
                $webPush->queueNotification($this->endpointOf($subscription), $payload);
            } catch (Throwable $error) {
                Log::warning('Web Push subscription rejected', [
                    'subscription' => $subscription->id,
                    'error' => $error->getMessage(),
                ]);
            }
        }

        $this->deliver($webPush, $subscriptions->keyBy('endpoint'));
    }

    /**
     * @return array<string, string>|null
     */
    private function vapid(): ?array
    {
        $public = (string) config('webpush.vapid.public_key');
        $private = (string) config('webpush.vapid.private_key');

        if ($public === '' || $private === '') {
            return null;
        }

        return [
            'subject' => (string) config('webpush.vapid.subject'),
            'publicKey' => $public,
            'privateKey' => $private,
        ];
    }

    private function endpointOf(PushSubscription $subscription): Subscription
    {
        return Subscription::create([
            'endpoint' => $subscription->endpoint,
            'publicKey' => $subscription->p256dh_key,
            'authToken' => $subscription->auth_key,
        ]);
    }

    /**
     * Send what is queued, and forget the subscriptions the browser vendor
     * says are gone. A 404 or 410 means that install was uninstalled or its
     * permission revoked; keeping the row means trying again on every order,
     * forever.
     *
     * @param Collection<string, PushSubscription> $byEndpoint
     */
    private function deliver(WebPush $webPush, Collection $byEndpoint): void
    {
        foreach ($webPush->flush() as $report) {
            if ($report->isSuccess()) {
                continue;
            }

            $endpoint = $report->getEndpoint();

            if ($report->isSubscriptionExpired()) {
                $byEndpoint->get($endpoint)?->delete();

                continue;
            }

            Log::warning('Web Push not delivered', [
                'endpoint' => $endpoint,
                'reason' => $report->getReason(),
            ]);
        }
    }

    public function failed(OrderCreated $event, Throwable $exception): void
    {
        Log::error('Web Push listener failed', ['error' => $exception->getMessage()]);
    }
}
