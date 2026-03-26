<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductVariant extends Model
{
    protected $fillable = [
        'product_id',
        'sku',
        'attributes',
        'price',
        'compare_price',
        'stock_quantity',
        'image',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'attributes' => 'array',
        'price' => 'decimal:2',
        'compare_price' => 'decimal:2',
        'stock_quantity' => 'integer',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true)->orderBy('sort_order');
    }

    public function effectivePrice(): float
    {
        return (float) ($this->price ?? $this->product->price);
    }

    public function label(): string
    {
        if (empty($this->attributes)) {
            return '';
        }
        $parts = [];
        foreach ($this->attributes as $attributeId => $valueId) {
            $attr = ProductAttribute::find($attributeId);
            $value = ProductAttributeValue::find($valueId);
            if ($attr && $value) {
                $parts[] = $attr->name . ': ' . $value->value;
            }
        }

        return implode(', ', $parts);
    }
}
