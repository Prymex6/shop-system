<?php

namespace App\Services;

use App\Models\Tenant\Product;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class ProductRecommendationService
{
    /**
     * Get products related to the given product (same category, sorted by popularity).
     */
    public function getRelated(Product $product, int $limit = 6): Collection
    {
        $query = Product::published()
            ->where('id', '!=', $product->id)
            ->with(['category', 'images']);

        // Prefer same category
        if ($product->category_id) {
            $query->where('category_id', $product->category_id);
        }

        // Sort by reviews rating then sales count
        $query->orderByDesc('reviews_avg')
            ->orderByDesc('sales_count')
            ->limit($limit);

        $related = $query->get();

        // If not enough related products found, fill with any popular products
        if ($related->count() < $limit) {
            $existingIds = $related->pluck('id')->push($product->id)->toArray();
            $remaining = $limit - $related->count();

            $extra = Product::published()
                ->whereNotIn('id', $existingIds)
                ->orderByDesc('sales_count')
                ->orderByDesc('reviews_avg')
                ->with(['category', 'images'])
                ->limit($remaining)
                ->get();

            $related = $related->merge($extra);
        }

        return $related;
    }

    /**
     * Get bestsellers (products with highest order counts).
     */
    public function getBestsellers(int $limit = 8): Collection
    {
        return Product::published()
            ->with(['category', 'images'])
            ->orderByDesc('sales_count')
            ->orderByDesc('reviews_avg')
            ->limit($limit)
            ->get();
    }

    /**
     * Get products frequently bought together with the given product.
     * Finds products that appear in the same orders as the given product.
     */
    public function getFrequentlyBoughtTogether(Product $product, int $limit = 4): Collection
    {
        // Find order IDs that contain this product
        $orderIds = DB::table('order_items')
            ->where('product_id', $product->id)
            ->pluck('order_id');

        if ($orderIds->isEmpty()) {
            return new Collection;
        }

        // Find other products in those orders
        $productIds = DB::table('order_items')
            ->whereIn('order_id', $orderIds)
            ->where('product_id', '!=', $product->id)
            ->select('product_id', DB::raw('COUNT(*) as freq'))
            ->groupBy('product_id')
            ->orderByDesc('freq')
            ->limit($limit)
            ->pluck('product_id');

        if ($productIds->isEmpty()) {
            return new Collection;
        }

        return Product::published()
            ->whereIn('id', $productIds)
            ->with(['category', 'images'])
            ->get()
            ->sortByDesc(fn ($p) => array_search($p->id, $productIds->toArray()))
            ->values();
    }
}
