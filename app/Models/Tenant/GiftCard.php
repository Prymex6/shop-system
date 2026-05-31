<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GiftCard extends Model
{
    protected $fillable = [
        'code',
        'initial_value',
        'current_value',
        'customer_id',
        'purchased_by_order_id',
        'expires_at',
        'is_active',
    ];

    protected $casts = [
        'initial_value' => 'decimal:2',
        'current_value' => 'decimal:2',
        'expires_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function purchasedByOrder(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'purchased_by_order_id');
    }

    public function isValid(): bool
    {
        if (!$this->is_active) {
            return false;
        }
        if ($this->current_value <= 0) {
            return false;
        }
        if ($this->expires_at && $this->expires_at->isPast()) {
            return false;
        }

        return true;
    }

    /**
     * Use the gift card for an order, deducting the specified amount.
     */
    public function use(Order $order, float $amount): void
    {
        $amount = min($amount, (float) $this->current_value);

        $this->decrement('current_value', $amount);
        $this->refresh();

        if ((float) $this->current_value <= 0) {
            $this->update(['is_active' => false]);
        }

        $order->update([
            'gift_card_id' => $this->id,
            'gift_card_discount' => $amount,
        ]);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeValid($query)
    {
        return $query->where('is_active', true)
            ->where('current_value', '>', 0)
            ->where(function ($q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
            });
    }
}
