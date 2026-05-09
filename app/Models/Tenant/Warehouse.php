<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Warehouse extends Model
{
    protected $fillable = [
        'name',
        'city',
        'address',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function stock(): HasMany
    {
        return $this->hasMany(ProductWarehouseStock::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
