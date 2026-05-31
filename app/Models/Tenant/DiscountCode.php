<?php

namespace App\Models\Tenant;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class DiscountCode extends Model
{
    protected $fillable = [
        'code',
        'type',
        'value',
        'min_order_value',
        'max_uses',
        'used_count',
        'valid_from',
        'valid_until',
        'is_active',
    ];

    protected $casts = [
        'value' => 'decimal:2',
        'min_order_value' => 'decimal:2',
        'max_uses' => 'integer',
        'used_count' => 'integer',
        'valid_from' => 'datetime',
        'valid_until' => 'datetime',
        'is_active' => 'boolean',
    ];

    /**
     * Validate if discount code can be used
     */
    public function canBeUsed(float $orderTotal): array
    {
        // Check if active
        if (!$this->is_active) {
            return [
                'valid' => false,
                'message' => 'Kod rabatowy jest nieaktywny',
            ];
        }

        // Check date validity — compare with Warsaw time since dates are stored in local timezone
        $tz = Setting::get('timezone', 'Europe/Warsaw');
        $now = Carbon::now($tz);
        $validFrom = $this->valid_from ? Carbon::parse($this->valid_from->format('Y-m-d H:i:s'), $tz) : null;
        $validUntil = $this->valid_until ? Carbon::parse($this->valid_until->format('Y-m-d H:i:s'), $tz) : null;

        if ($validFrom && $now->lt($validFrom)) {
            return [
                'valid' => false,
                'message' => __('messages.discount_not_yet_valid'),
            ];
        }

        if ($validUntil && $now->gt($validUntil)) {
            return [
                'valid' => false,
                'message' => __('messages.discount_expired'),
            ];
        }

        // Check usage limit
        if ($this->max_uses && $this->used_count >= $this->max_uses) {
            return [
                'valid' => false,
                'message' => __('messages.discount_max_uses'),
            ];
        }

        // Check minimum order value
        if ($this->min_order_value && $orderTotal < $this->min_order_value) {
            return [
                'valid' => false,
                'message' => __('messages.minimum_order_value', ['amount' => number_format($this->min_order_value, 2, ',', ' ') . ' PLN']),
            ];
        }

        return [
            'valid' => true,
            'message' => __('messages.discount_valid'),
        ];
    }

    /**
     * Calculate discount amount
     */
    public function calculateDiscount(float $orderTotal): float
    {
        if ($this->type === 'percentage') {
            $pct = max(0, min(100, (float) $this->value));

            return ($orderTotal * $pct) / 100;
        }

        return max(0, min((float) $this->value, $orderTotal));
    }

    /**
     * Increment usage count
     */
    public function incrementUsage(): void
    {
        $this->increment('used_count');
    }

    /**
     * Scope: Active codes
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: Valid codes (date range)
     */
    public function scopeValid($query)
    {
        $timezone = Setting::get('timezone', 'Europe/Warsaw');
        $now = Carbon::now($timezone)->format('Y-m-d H:i:s');

        return $query->where(function ($q) use ($now) {
            $q->where('valid_from', '<=', $now)
                ->orWhereNull('valid_from');
        })->where(function ($q) use ($now) {
            $q->where('valid_until', '>=', $now)
                ->orWhereNull('valid_until');
        });
    }

    /**
     * Scope: Not exhausted (usage limit)
     */
    public function scopeNotExhausted($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('max_uses')
                ->orWhereRaw('used_count < max_uses');
        });
    }

    /**
     * Get formatted discount value
     */
    public function getFormattedValueAttribute(): string
    {
        if ($this->type === 'percentage') {
            return $this->value . '%';
        }

        return number_format($this->value, 2, ',', ' ') . ' PLN';
    }

    /**
     * Get usage percentage
     */
    public function getUsagePercentageAttribute(): ?float
    {
        if (!$this->max_uses) {
            return null;
        }

        return ($this->used_count / $this->max_uses) * 100;
    }
}
