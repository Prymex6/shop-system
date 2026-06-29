<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class RmaRequest extends Model
{
    protected $fillable = [
        'order_id',
        'rma_number',
        'customer_email',
        'customer_name',
        'status',
        'reason',
        'items',
        'condition_notes',
        'approved_at',
        'received_at',
        'refund_amount',
    ];

    protected $casts = [
        'items' => 'array',
        'approved_at' => 'datetime',
        'received_at' => 'datetime',
        'refund_amount' => 'decimal:2',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function generateRmaNumber(): string
    {
        do {
            $number = 'RMA-' . now()->format('ymd') . '-' . strtoupper(Str::random(6));
        } while (static::where('rma_number', $number)->exists());

        return $number;
    }
}
