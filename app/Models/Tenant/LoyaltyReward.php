<?php

namespace App\Models\Tenant;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LoyaltyReward extends Model
{
    protected $fillable = [
        'name', 'description', 'cost_points', 'type', 'value',
        'product_id', 'variant_id', 'is_active', 'valid_from', 'valid_until',
        'max_uses', 'used_count', 'sort_order',
    ];

    protected $casts = [
        'cost_points' => 'integer',
        'value' => 'decimal:2',
        'is_active' => 'boolean',
        'valid_from' => 'datetime',
        'valid_until' => 'datetime',
        'max_uses' => 'integer',
        'used_count' => 'integer',
        'sort_order' => 'integer',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function productVariant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'variant_id');
    }

    public function isAvailable(): bool
    {
        if (!$this->is_active) {
            return false;
        }
        $tz = Setting::get('timezone', 'Europe/Warsaw');
        $now = Carbon::now($tz);
        $validFrom = $this->valid_from ? Carbon::parse($this->valid_from->format('Y-m-d H:i:s'), $tz) : null;
        $validUntil = $this->valid_until ? Carbon::parse($this->valid_until->format('Y-m-d H:i:s'), $tz) : null;
        if ($validFrom && $now->lt($validFrom)) {
            return false;
        }
        if ($validUntil && $now->gt($validUntil)) {
            return false;
        }
        if ($this->max_uses !== null && $this->used_count >= $this->max_uses) {
            return false;
        }

        return true;
    }

    public function scopeAvailable($query)
    {
        $tz = Setting::get('timezone', 'Europe/Warsaw');
        $now = Carbon::now($tz)->format('Y-m-d H:i:s');

        return $query->where('is_active', true)
            ->where(fn ($q) => $q->whereNull('valid_from')->orWhere('valid_from', '<=', $now))
            ->where(fn ($q) => $q->whereNull('valid_until')->orWhere('valid_until', '>=', $now))
            ->where(fn ($q) => $q->whereNull('max_uses')->orWhereColumn('used_count', '<', 'max_uses'))
            ->orderBy('sort_order');
    }
}
