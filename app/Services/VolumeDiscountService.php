<?php

namespace App\Services;

use App\Models\Tenant\Product;
use App\Models\Tenant\VolumeDiscount;

class VolumeDiscountService
{
    public function calculate(Product $product, int $quantity): float
    {
        $basePrice = (float) $product->price;

        // Find applicable discount — product-specific first, then category
        $discount = VolumeDiscount::active()
            ->where(function ($q) use ($product) {
                $q->where('product_id', $product->id)
                    ->orWhere(function ($q2) use ($product) {
                        $q2->whereNull('product_id')
                            ->where('category_id', $product->category_id);
                    });
            })
            ->where('min_quantity', '<=', $quantity)
            ->orderByDesc('product_id') // product-specific wins
            ->orderByDesc('min_quantity') // highest threshold wins
            ->first();

        if (!$discount) {
            return $basePrice;
        }

        return match ($discount->discount_type) {
            'percentage' => round($basePrice * (1 - $discount->discount_value / 100), 2),
            'fixed' => max(0, $basePrice - $discount->discount_value),
            default => $basePrice,
        };
    }
}
