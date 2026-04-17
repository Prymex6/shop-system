<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OrderItem extends Model
{
    protected $fillable = [
        'order_id',
        'product_id',
        'variant_id',
        'bundle_id',
        'bundle_name',
        'name',
        'sku',
        'variant_label',
        'product_type',
        'price',
        'tax_rate',
        'quantity',
        'total',
        'fulfillment_status',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'tax_rate' => 'decimal:2',
        'total' => 'decimal:2',
        'quantity' => 'integer',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'variant_id');
    }

    public function downloadLinks(): HasMany
    {
        return $this->hasMany(DownloadLink::class, 'order_item_id');
    }

    public function bundle(): BelongsTo
    {
        return $this->belongsTo(ProductBundle::class, 'bundle_id');
    }

    public function isDigital(): bool
    {
        return $this->product_type === 'digital';
    }

    public function isPhysical(): bool
    {
        return $this->product_type === 'physical';
    }
}
