<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class ShippingMethod extends Model
{
    protected $fillable = [
        'name',
        'description',
        'type',
        'carrier',
        'price',
        'free_from',
        'delivery_days_min',
        'delivery_days_max',
        'weight_min',
        'weight_max',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'free_from' => 'decimal:2',
        'weight_min' => 'decimal:3',
        'weight_max' => 'decimal:3',
        'is_active' => 'boolean',
        'delivery_days_min' => 'integer',
        'delivery_days_max' => 'integer',
    ];

    public function zones(): BelongsToMany
    {
        return $this->belongsToMany(ShippingZone::class, 'shipping_zone_methods', 'method_id', 'zone_id')
            ->withPivot(['price_override', 'free_from_override']);
    }

    /**
     * Carriers whose lockers a customer picks from at checkout.
     *
     * A method with no carrier is delivered however the shop delivers it —
     * there is nothing to pick and nothing to buy a label for.
     */
    public const PICKUP_POINT_CARRIERS = ['inpost'];

    public function requiresPickupPoint(): bool
    {
        return in_array($this->carrier, self::PICKUP_POINT_CARRIERS, true);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true)->orderBy('sort_order');
    }

    public function deliveryLabel(): string
    {
        if ($this->delivery_days_min === $this->delivery_days_max) {
            return "{$this->delivery_days_min} dni roboczych";
        }

        return "{$this->delivery_days_min}–{$this->delivery_days_max} dni roboczych";
    }

    public function priceForOrder(float $subtotal): float
    {
        if ($this->free_from && $subtotal >= $this->free_from) {
            return 0.0;
        }

        return (float) $this->price;
    }
}
