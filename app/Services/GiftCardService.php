<?php

namespace App\Services;

use App\Models\Tenant\GiftCard;
use App\Models\Tenant\Order;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class GiftCardService
{
    /**
     * Generate gift cards.
     *
     * @return Collection<GiftCard>
     */
    public function generate(int $count, float $value, ?Carbon $expiresAt = null): Collection
    {
        $cards = collect();

        for ($i = 0; $i < $count; $i++) {
            $code = $this->generateUniqueCode();

            $card = GiftCard::create([
                'code' => $code,
                'initial_value' => $value,
                'current_value' => $value,
                'expires_at' => $expiresAt,
                'is_active' => true,
            ]);

            $cards->push($card);
        }

        return $cards;
    }

    /**
     * Check a gift card code and return the applicable discount for an order.
     *
     * @throws \InvalidArgumentException
     */
    public function apply(string $code, Order $order): float
    {
        $card = GiftCard::where('code', strtoupper(trim($code)))->first();

        if (!$card) {
            throw new \InvalidArgumentException('Karta podarunkowa nie istnieje.');
        }

        if (!$card->isValid()) {
            throw new \InvalidArgumentException(__('messages.gift_card_invalid'));
        }

        $discount = min((float) $card->current_value, (float) $order->total);

        return $discount;
    }

    /**
     * Redeem a gift card (deduct amount). Row-locked inside a transaction so two
     * concurrent checkouts using the same code can't both read the same balance
     * and each deduct from it — without the lock, that lets a card be spent for
     * more than it's actually worth.
     */
    public function redeem(GiftCard $card, float $amount): float
    {
        return DB::transaction(function () use ($card, $amount) {
            $locked = GiftCard::where('id', $card->id)->lockForUpdate()->first();

            $deduct = min($amount, (float) $locked->current_value);
            $locked->decrement('current_value', $deduct);

            if ((float) $locked->fresh()->current_value <= 0) {
                $locked->update(['is_active' => false]);
            }

            return $deduct;
        });
    }

    /**
     * Generate a unique gift card code.
     */
    protected function generateUniqueCode(): string
    {
        do {
            $code = strtoupper(Str::random(4)) . '-' . strtoupper(Str::random(4)) . '-' . strtoupper(Str::random(4));
        } while (GiftCard::where('code', $code)->exists());

        return $code;
    }
}
