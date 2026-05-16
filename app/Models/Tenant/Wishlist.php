<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Wishlist extends Model
{
    protected $fillable = [
        'customer_id', 'product_id', 'price_at_added',
        'price_drop_notified', 'was_out_of_stock', 'restock_notified',
    ];

    protected $casts = [
        'price_at_added' => 'decimal:2',
        'price_drop_notified' => 'boolean',
        'was_out_of_stock' => 'boolean',
        'restock_notified' => 'boolean',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
