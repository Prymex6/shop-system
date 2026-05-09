<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

class PurchaseOrder extends Model
{
    protected $fillable = [
        'supplier_id',
        'po_number',
        'status',
        'total_cost',
        'notes',
        'expected_at',
        'received_at',
    ];

    protected $casts = [
        'total_cost' => 'decimal:2',
        'expected_at' => 'date',
        'received_at' => 'datetime',
    ];

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(PurchaseOrderItem::class);
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($po) {
            if (empty($po->po_number)) {
                $po->po_number = DB::transaction(function () {
                    $prefix = 'PO-' . now()->format('ymd') . '-';
                    $last = DB::table('purchase_orders')
                        ->where('po_number', 'like', $prefix . '%')
                        ->lockForUpdate()
                        ->max('po_number');
                    $seq = $last ? ((int) substr($last, strlen($prefix))) + 1 : 1;

                    return $prefix . str_pad($seq, 4, '0', STR_PAD_LEFT);
                });
            }
        });
    }
}
