<?php

namespace App\Http\Controllers\Tenant\Client;

use App\Http\Concerns\ValidatesHoneypot;
use App\Http\Controllers\Controller;
use App\Models\Tenant\NewsletterSubscriber;
use Illuminate\Http\Request;

class NewsletterController extends Controller
{
    use ValidatesHoneypot;

    public function subscribe(Request $request)
    {
        if ($this->isHoneypotFilled($request)) {
            return back()->with('success', __('messages.newsletter_thanks'));
        }

        $validated = $request->validate([
            'email' => ['required', 'email', 'max:255'],
        ]);

        $email = strtolower($validated['email']);

        $subscriber = NewsletterSubscriber::firstOrCreate(['email' => $email]);
        if ($subscriber->unsubscribed_at) {
            $subscriber->update(['unsubscribed_at' => null]);
        }

        return back()->with('success', __('messages.newsletter_thanks'));
    }
}
