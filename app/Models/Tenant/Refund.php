<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Refund extends Model
{
    protected $fillable = [
        'order_id',
        'amount',
        'reason',
        'notes',
        'status',
        'gateway_refund_id',
        'items',
        'images',
        'processed_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'items' => 'array',
        'images' => 'array',
        'processed_at' => 'datetime',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function isProcessable(): bool
    {
        return in_array($this->status, ['approved']);
    }
}
