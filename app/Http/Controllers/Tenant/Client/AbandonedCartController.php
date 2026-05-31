<?php

namespace App\Http\Controllers\Tenant\Client;

use App\Http\Concerns\ValidatesHoneypot;
use App\Http\Controllers\Controller;
use App\Models\Tenant\AbandonedCart;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AbandonedCartController extends Controller
{
    use ValidatesHoneypot;

    /**
     * Track / update the current cart state (called by JS).
     * POST /api/cart/track
     *
     * Unauthenticated by design (guest carts must be trackable before
     * login), but that also means `email` is fully attacker-supplied and
     * feeds directly into SendAbandonedCartReminder, which sends a real
     * marketing email to it ~60 minutes later. Unlike the other public,
     * no-login endpoints (Contact/Newsletter/Chat), this one had no
     * honeypot at all — the highest-impact one of the four to leave open,
     * since it's the only one whose abuse mails an arbitrary third party.
     */
    public function track(Request $request): JsonResponse
    {
        $request->validate([
            'session_id' => ['required', 'string', 'max:100'],
            'email' => ['nullable', 'email', 'max:255'],
            'items' => ['nullable', 'array'],
        ]);

        if ($this->isHoneypotFilled($request)) {
            return response()->json(['tracked' => true]);
        }

        $customer = Auth::guard('customer')->user();

        $cartData = [
            'items' => $request->items ?? [],
            'updated_at' => now()->toISOString(),
        ];

        // If cart is empty, remove record
        if (empty($request->items)) {
            AbandonedCart::where('session_id', $request->session_id)->delete();

            return response()->json(['tracked' => false]);
        }

        $email = $request->email ?? $customer?->email;

        AbandonedCart::updateOrCreate(
            ['session_id' => $request->session_id],
            [
                'customer_id' => $customer?->id,
                'email' => $email,
                'cart_data' => $cartData,
                'reminder_sent_at' => null, // reset reminder so it can be sent again after update
                'converted_at' => null,
            ]
        );

        return response()->json(['tracked' => true]);
    }

    /**
     * Mark a cart as converted (called after successful order).
     * POST /api/cart/convert
     */
    public function convert(Request $request): JsonResponse
    {
        $request->validate([
            'session_id' => ['required', 'string', 'max:100'],
        ]);

        AbandonedCart::where('session_id', $request->session_id)
            ->whereNull('converted_at')
            ->update(['converted_at' => now()]);

        return response()->json(['converted' => true]);
    }
}
