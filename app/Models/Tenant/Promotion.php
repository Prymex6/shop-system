<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;

class Promotion extends Model
{
    protected $fillable = [
        'name',
        'banner_text',
        'starts_at',
        'ends_at',
        'discount_code',
        'is_active',
    ];

    protected $casts = [
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    public function isCurrentlyActive(): bool
    {
        if (!$this->is_active) {
            return false;
        }
        $now = now();

        return $this->starts_at <= $now && $this->ends_at >= $now;
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true)
            ->where('starts_at', '<=', now())
            ->where('ends_at', '>=', now());
    }
}
