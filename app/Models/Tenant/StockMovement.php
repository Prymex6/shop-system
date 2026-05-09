<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockMovement extends Model
{
    protected $fillable = [
        'product_id',
        'variant_id',
        'quantity_change',
        'quantity_after',
        'type',
        'reason',
        'reference_id',
        'reference_type',
    ];

    protected $casts = [
        'quantity_change' => 'integer',
        'quantity_after' => 'integer',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'variant_id');
    }
}
