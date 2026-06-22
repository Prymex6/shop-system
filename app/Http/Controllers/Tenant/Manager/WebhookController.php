<?php

namespace App\Http\Controllers\Tenant\Manager;

use App\Http\Controllers\Controller;
use App\Models\Tenant\Webhook;
use App\Rules\PublicHttpUrl;
use App\Services\WebhookDispatcherService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;

class WebhookController extends Controller
{
    public function __construct(protected WebhookDispatcherService $dispatcher) {}

    public function index()
    {
        $webhooks = Webhook::orderBy('name')->paginate(25);

        return Inertia::render('Tenant/Manager/Webhooks/Index', [
            'webhooks' => $webhooks,
            'availableEvents' => $this->availableEvents(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'url' => ['required', 'url', 'max:500', new PublicHttpUrl],
            'events' => 'required|array|min:1',
            'events.*' => 'string',
            'secret' => 'nullable|string|max:64',
            'is_active' => 'boolean',
        ]);

        Webhook::create($data);

        return back()->with('success', 'Webhook dodany.');
    }

    public function update(Request $request, Webhook $webhook)
    {
        // 'secret' is intentionally NOT accepted here — it's a signing key the
        // receiving endpoint uses to verify authenticity, so it must never be
        // silently overwritten (or weakened) via a free-text edit form. Use
        // regenerateSecret() to rotate it instead.
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'url' => ['required', 'url', 'max:500', new PublicHttpUrl],
            'events' => 'required|array|min:1',
            'events.*' => 'string',
            'is_active' => 'boolean',
        ]);

        $webhook->update($data);

        return back()->with('success', 'Webhook zaktualizowany.');
    }

    public function regenerateSecret(Webhook $webhook)
    {
        $secret = Str::random(32);
        $webhook->update(['secret' => $secret]);

        // Shown once, directly in the response — never persisted back to the
        // client afterward (the model hides `secret` from serialization).
        return back()->with('success', 'Nowy sekret wygenerowany.')->with('new_secret', $secret);
    }

    public function destroy(Webhook $webhook)
    {
        $webhook->delete();

        return back()->with('success', __('messages.webhook_deleted'));
    }

    public function test(Webhook $webhook)
    {
        $this->dispatcher->dispatch('webhook.test', [
            'message' => 'This is a test ping from ' . config('app.name'),
            'timestamp' => now()->toIso8601String(),
        ]);

        return back()->with('success', 'Testowy ping wysłany do ' . $webhook->url);
    }

    protected function availableEvents(): array
    {
        return [
            'order.created',
            'order.status_changed',
            'order.shipped',
            'order.delivered',
            'order.cancelled',
            'refund.requested',
            'refund.processed',
            'webhook.test',
        ];
    }
}
