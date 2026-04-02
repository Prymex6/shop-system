<?php

namespace App\Http\Controllers\Tenant\Client;

use App\Http\Controllers\Controller;
use App\Models\Tenant\Product;
use App\Models\Tenant\ProductAttribute;
use App\Models\Tenant\Setting;
use App\Services\FlashSaleService;
use App\Services\ProductRecommendationService;
use Inertia\Inertia;

class ProductController extends Controller
{
    public function __construct(
        protected ProductRecommendationService $recommendations,
        protected FlashSaleService $flashSaleService,
    ) {}

    public function show(Product $product)
    {
        if (!$product->is_published) {
            abort(404);
        }

        $product->load([
            'category.parent',
            'images',
            'variants.product',
            'taxRate',
            'features',
            'reviews' => fn ($q) => $q->approved()->with('customer')->latest()->limit(20),
        ]);

        $related = $this->recommendations->getRelated($product, 6);
        $frequentlyBoughtWith = $this->recommendations->getFrequentlyBoughtTogether($product, 4);

        $attributes = [];
        if ($product->variants->isNotEmpty()) {
            $attributeIds = collect($product->variants->pluck('attributes')->flatten(1)->keys())->unique();
            $attributes = ProductAttribute::with('values')
                ->whereIn('id', $attributeIds)
                ->get();
        }

        $flashSale = $this->flashSaleService->getActiveForProduct($product);
        $flashPrice = $flashSale ? $flashSale->calculatePrice($product) : null;

        // Lets ProductReviews.vue send order_id with a new review so
        // ReviewController::store() can mark it is_verified_purchase — without
        // this, the storefront review form (the only one customers actually
        // use) never sends order_id and "✓ Zweryfikowany zakup" never appears.
        $verifiedOrderId = null;
        if ($customer = auth('customer')->user()) {
            $verifiedOrderId = $customer->orders()
                ->whereHas('items', fn ($q) => $q->where('product_id', $product->id))
                ->latest()
                ->value('id');
        }

        // Site-wide trust content (guarantee badges / testimonials / FAQ) — the
        // same blocks a manager configures once in the Homepage Builder, reused
        // here so the product page reflects them without a second content system.
        $homepageBlocks = Setting::get('homepage_blocks');
        $homepageBlocks = $homepageBlocks ? (is_string($homepageBlocks) ? json_decode($homepageBlocks, true) : $homepageBlocks) : [];
        $siteBlocks = collect($homepageBlocks)
            ->whereIn('id', ['guarantee_badges', 'testimonials', 'faq'])
            ->where('enabled', true)
            ->values()
            ->all();

        return Inertia::render('Tenant/Client/Shop/Product', [
            'product' => $product,
            'related' => $related,
            'frequentlyBoughtWith' => $frequentlyBoughtWith,
            'attributes' => $attributes,
            'flashPrice' => $flashPrice,
            'flashSaleEndsAt' => $flashSale?->ends_at?->toIso8601String(),
            'verifiedOrderId' => $verifiedOrderId,
            'siteBlocks' => $siteBlocks,
        ]);
    }

    /**
     * GET /produkt/{slug}/related
     * JSON endpoint for related products (AJAX).
     */
    public function related(Product $product)
    {
        if (!$product->is_published) {
            return response()->json([]);
        }

        $related = $this->recommendations->getRelated($product, 6);

        return response()->json($related);
    }
}
