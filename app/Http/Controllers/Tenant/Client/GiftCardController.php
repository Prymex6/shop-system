<?php

namespace App\Http\Controllers\Tenant\Client;

use App\Http\Controllers\Controller;
use App\Models\Tenant\GiftCard;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GiftCardController extends Controller
{
    /**
     * Check a gift card code and return its current value.
     * POST /kasa/gift-card
     */
    public function check(Request $request): JsonResponse
    {
        $request->validate([
            'code' => ['required', 'string'],
            'total' => ['nullable', 'numeric', 'min:0'],
        ]);

        $code = strtoupper(trim($request->code));
        $card = GiftCard::where('code', $code)->first();

        if (!$card) {
            return response()->json([
                'valid' => false,
                'message' => 'Karta podarunkowa nie istnieje.',
            ], 422);
        }

        if (!$card->isValid()) {
            return response()->json([
                'valid' => false,
                'message' => __('messages.gift_card_invalid'),
            ], 422);
        }

        $total = (float) ($request->total ?? 0);
        $applicableAmount = $total > 0 ? min((float) $card->current_value, $total) : (float) $card->current_value;

        return response()->json([
            'valid' => true,
            'message' => __('messages.gift_card_valid'),
            'code' => $card->code,
            'current_value' => $card->current_value,
            'amount' => $applicableAmount,
            'formatted_amount' => number_format($applicableAmount, 2, ',', ' ') . ' zł',
            'expires_at' => $card->expires_at?->format('Y-m-d'),
        ]);
    }
}
