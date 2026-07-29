<?php

namespace App\Http\Controllers\Tenant\Client;

use App\Http\Concerns\ValidatesHoneypot;
use App\Http\Controllers\Controller;
use App\Mail\Tenant\ContactInquiryManagerMail;
use App\Models\Tenant\ContactInquiry;
use App\Models\Tenant\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Inertia\Inertia;

class ContactController extends Controller
{
    use ValidatesHoneypot;

    public function index()
    {
        return Inertia::render('Tenant/Client/Contact');
    }

    public function send(Request $request)
    {
        if ($this->isHoneypotFilled($request)) {
            return response()->json(['message' => __('messages.message_sent')]);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'message' => 'required|string|max:5000',
        ]);

        // Persisted before mail is even attempted — previously an unset
        // shop_email silently dropped the message with no record anywhere,
        // while the endpoint still reported success to the visitor.
        ContactInquiry::create($validated);

        $shopEmail = Setting::get('shop_email');
        if ($shopEmail) {
            try {
                Mail::to($shopEmail)->queue(new ContactInquiryManagerMail(
                    senderName: $validated['name'],
                    senderEmail: $validated['email'],
                    senderMessage: $validated['message'],
                ));
            } catch (\Exception $e) {
                Log::warning('Contact form email failed: ' . $e->getMessage());
            }
        }

        return response()->json(['message' => __('messages.message_sent')]);
    }
}
