<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class ShippingZone extends Model
{
    protected $fillable = [
        'name',
        'countries',
        'is_default',
    ];

    protected $casts = [
        'countries' => 'array',
        'is_default' => 'boolean',
    ];

    public function methods(): BelongsToMany
    {
        return $this->belongsToMany(ShippingMethod::class, 'shipping_zone_methods', 'zone_id', 'method_id')
            ->withPivot(['price_override', 'free_from_override']);
    }
}
