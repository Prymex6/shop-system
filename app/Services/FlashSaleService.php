<?php

namespace App\Services;

use App\Models\Tenant\FlashSale;
use App\Models\Tenant\Product;

class FlashSaleService
{
    public function getActiveForProduct(Product $product): ?FlashSale
    {
        return FlashSale::where('is_active', true)
            ->where('starts_at', '<=', now())
            ->where('ends_at', '>=', now())
            ->where(function ($q) {
                $q->whereNull('max_orders')
                    ->orWhereColumn('orders_count', '<', 'max_orders');
            })
            ->whereHas('products', fn ($q) => $q->where('product_id', $product->id))
            ->first();
    }

    public function getFlashPrice(Product $product): ?float
    {
        $sale = $this->getActiveForProduct($product);
        if (!$sale) {
            return null;
        }

        return $sale->calculatePrice($product);
    }
}
