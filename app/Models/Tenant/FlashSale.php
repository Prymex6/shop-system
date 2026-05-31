<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class FlashSale extends Model
{
    protected $fillable = [
        'name',
        'discount_type',
        'discount_value',
        'starts_at',
        'ends_at',
        'max_orders',
        'orders_count',
        'is_active',
    ];

    protected $casts = [
        'discount_value' => 'decimal:2',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'is_active' => 'boolean',
        'max_orders' => 'integer',
        'orders_count' => 'integer',
    ];

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'flash_sale_products')
            ->withPivot('custom_price');
    }

    public function isActive(): bool
    {
        if (!$this->is_active) {
            return false;
        }
        if (now()->lt($this->starts_at) || now()->gt($this->ends_at)) {
            return false;
        }
        if ($this->max_orders !== null && $this->orders_count >= $this->max_orders) {
            return false;
        }

        return true;
    }

    public function calculatePrice(Product $product): ?float
    {
        $pivot = $this->products()->where('product_id', $product->id)->first()?->pivot;
        if ($pivot && $pivot->custom_price !== null) {
            return (float) $pivot->custom_price;
        }

        $basePrice = (float) $product->price;

        return match ($this->discount_type) {
            'percentage' => round($basePrice * (1 - $this->discount_value / 100), 2),
            'fixed' => max(0, $basePrice - $this->discount_value),
            default => $basePrice,
        };
    }
}
