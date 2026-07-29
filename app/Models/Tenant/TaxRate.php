<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TaxRate extends Model
{
    protected $fillable = [
        'name',
        'rate',
        'country',
        'is_default',
        'is_active',
        // Added by 2026_03_22_300004_extend_tax_rates_for_eu_oss — omitting
        // these here meant TaxController::storeOss()'s "Add OSS rate" form
        // silently discarded every one of them on mass-assignment, so the
        // rate saved with none of the EU OSS fields actually set.
        'country_code',
        'is_eu_oss',
        'eu_vat_rate',
    ];

    protected $casts = [
        'rate' => 'decimal:2',
        'is_default' => 'boolean',
        'is_active' => 'boolean',
        'is_eu_oss' => 'boolean',
        'eu_vat_rate' => 'decimal:2',
    ];

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public static function getDefault(): ?self
    {
        return static::where('is_default', true)->where('is_active', true)->first();
    }
}
