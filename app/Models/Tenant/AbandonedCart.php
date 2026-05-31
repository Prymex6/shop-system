<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AbandonedCart extends Model
{
    protected $fillable = [
        'session_id',
        'customer_id',
        'email',
        'cart_data',
        'reminder_sent_at',
        'converted_at',
    ];

    protected $casts = [
        'cart_data' => 'array',
        'reminder_sent_at' => 'datetime',
        'converted_at' => 'datetime',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function scopePending($query)
    {
        return $query->whereNull('reminder_sent_at')->whereNull('converted_at');
    }

    public function scopeEligibleForReminder($query, int $minutesOld = 60)
    {
        return $query->whereNull('reminder_sent_at')
            ->whereNull('converted_at')
            ->where('created_at', '<=', now()->subMinutes($minutesOld))
            ->whereNotNull('email');
    }

    public function markReminderSent(): void
    {
        $this->update(['reminder_sent_at' => now()]);
    }

    public function markConverted(): void
    {
        $this->update(['converted_at' => now()]);
    }
}
