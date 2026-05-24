<?php

namespace App\Models\Tenant;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class LoyaltyCampaign extends Model
{
    protected $fillable = [
        'name', 'multiplier', 'applies_to', 'target_id',
        'day_of_week', 'valid_from', 'valid_until', 'is_active',
    ];

    protected $casts = [
        'multiplier' => 'decimal:2',
        'is_active' => 'boolean',
        'valid_from' => 'datetime',
        'valid_until' => 'datetime',
        'day_of_week' => 'integer',
    ];

    public function isActiveNow(): bool
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
        if ($this->day_of_week !== null && $now->dayOfWeek !== $this->day_of_week) {
            return false;
        }

        return true;
    }

    public function scopeActiveNow($query)
    {
        $tz = Setting::get('timezone', 'Europe/Warsaw');
        $now = Carbon::now($tz)->format('Y-m-d H:i:s');
        $dow = Carbon::now($tz)->dayOfWeek;

        return $query->where('is_active', true)
            ->where(fn ($q) => $q->whereNull('valid_from')->orWhere('valid_from', '<=', $now))
            ->where(fn ($q) => $q->whereNull('valid_until')->orWhere('valid_until', '>=', $now))
            ->where(fn ($q) => $q->whereNull('day_of_week')->orWhere('day_of_week', $dow));
    }
}
