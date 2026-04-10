<?php

namespace App\Http\Controllers\Tenant\Client;

use App\Http\Controllers\Controller;
use App\Models\Tenant\Product;
use App\Services\ProductRecommendationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CartUpsellController extends Controller
{
    /**
     * Cross-sell suggestions for the cart sidebar ("Dodaj też:"). Reuses
     * ProductRecommendationService::getFrequentlyBoughtTogether(), which
     * already existed (product page only) but had no cart-page equivalent —
     * the cart itself never suggested anything beyond what's already in it.
     */
    public function suggestions(Request $request, ProductRecommendationService $recommendations): JsonResponse
    {
        $request->validate([
            'product_ids' => ['nullable', 'array'],
            'product_ids.*' => ['integer'],
        ]);

        $cartIds = collect($request->input('product_ids', []))->map(fn ($id) => (int) $id)->filter()->unique();

        $suggestions = collect();

        foreach ($cartIds as $productId) {
            $product = Product::published()->find($productId);
            if (!$product) {
                continue;
            }

            $suggestions = $suggestions->merge($recommendations->getFrequentlyBoughtTogether($product, 4));
        }

        $suggestions = $suggestions->unique('id')->reject(fn ($p) => $cartIds->contains($p->id));

        if ($suggestions->count() < 4) {
            $extra = $recommendations->getBestsellers(8)
                ->reject(fn ($p) => $cartIds->contains($p->id) || $suggestions->pluck('id')->contains($p->id));
            $suggestions = $suggestions->merge($extra);
        }

        return response()->json([
            'products' => $suggestions->take(4)->values()->map(fn ($p) => [
                'id' => $p->id,
                'name' => $p->name,
                'slug' => $p->slug,
                'type' => $p->type,
                'price' => (float) $p->price,
                'image' => $p->images->first()?->path,
            ]),
        ]);
    }
}
