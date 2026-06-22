<?php

namespace App\Services;

use App\Models\Tenant\Webhook;
use App\Rules\PublicHttpUrl;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WebhookDispatcherService
{
    public function dispatch(string $event, array $payload): void
    {
        $webhooks = Webhook::active()->forEvent($event)->get();

        foreach ($webhooks as $webhook) {
            $this->send($webhook, $event, $payload);
        }
    }

    protected function send(Webhook $webhook, string $event, array $payload): void
    {
        // Re-check at send time, not just at save time — a hostname that was
        // public when the webhook was created could have been re-pointed at an
        // internal IP since (DNS rebinding).
        $host = parse_url($webhook->url, PHP_URL_HOST) ?: '';
        if (PublicHttpUrl::isBlockedHost($host)) {
            Log::warning("Webhook {$webhook->id} skipped: URL resolves to a blocked host.");
            $webhook->increment('failure_count');

            return;
        }

        $body = json_encode([
            'event' => $event,
            'timestamp' => now()->toIso8601String(),
            'payload' => $payload,
        ]);

        $signature = hash_hmac('sha256', $body, $webhook->secret ?? '');

        try {
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'X-Webhook-Event' => $event,
                'X-Webhook-Signature' => 'sha256=' . $signature,
            ])->timeout(10)->post($webhook->url, json_decode($body, true));

            if ($response->successful()) {
                $webhook->update([
                    'last_triggered_at' => now(),
                    'failure_count' => 0,
                ]);
            } else {
                $webhook->increment('failure_count');
                Log::warning("Webhook {$webhook->id} failed: HTTP " . $response->status());
            }
        } catch (\Throwable $e) {
            $webhook->increment('failure_count');
            Log::error("Webhook {$webhook->id} exception: " . $e->getMessage());

            // Auto-disable after 10 consecutive failures
            if ($webhook->fresh()->failure_count >= 10) {
                $webhook->update(['is_active' => false]);
                Log::warning("Webhook {$webhook->id} auto-disabled after 10 failures.");
            }
        }
    }
}
