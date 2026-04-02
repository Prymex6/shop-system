<?php

namespace App\Http\Controllers\Tenant\Client;

use App\Http\Controllers\Controller;
use App\Models\Tenant\Category;
use App\Models\Tenant\Customer;
use App\Models\Tenant\Product;
use App\Models\Tenant\ProductReview;
use App\Models\Tenant\Setting;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ShopController extends Controller
{
    public function index()
    {
        $featuredProducts = Product::published()
            ->featured()
            ->with(['images', 'category'])
            ->inStock()
            ->orderBy('sort_order')
            ->limit(12)
            ->get();

        $newProducts = Product::published()
            ->with(['images', 'category'])
            ->inStock()
            ->latest()
            ->limit(4)
            ->get();

        $categories = Category::active()
            ->roots()
            ->with('children')
            ->ordered()
            ->get();

        $bestSellers = Product::published()
            ->with(['images', 'category'])
            ->orderByDesc('sales_count')
            ->limit(4)
            ->get();

        $blocks = Setting::get('homepage_blocks');
        $homepageBlocks = $blocks ? (is_string($blocks) ? json_decode($blocks, true) : $blocks) : [];
        $heroBlock = collect($homepageBlocks)->firstWhere('id', 'hero');

        // Real, live numbers for the hero's trust row — not marketing copy, so
        // each figure is omitted (not zero-padded/faked) until the shop
        // actually has enough of that signal to be worth showing.
        $reviewsAvg = ProductReview::approved()->avg('rating');
        $reviewsCount = ProductReview::approved()->count();

        return Inertia::render('Tenant/Client/Shop/Index', [
            'featuredProducts' => $featuredProducts,
            'newProducts' => $newProducts,
            'categories' => $categories,
            'bestSellers' => $bestSellers,
            'shopName' => Setting::get('shop_name', 'Sklep'),
            'heroImageUrl' => Setting::get('hero_image_url'),
            'heroTitle' => Setting::get('hero_title'),
            'heroSubtitle' => Setting::get('hero_subtitle'),
            'heroBadge' => $heroBlock['settings']['subheading'] ?? null,
            'logoUrl' => Setting::get('logo_url'),
            'homepageBlocks' => $homepageBlocks,
            'stats' => [
                'products' => Product::published()->count(),
                'customers' => Customer::count(),
                'reviews_avg' => $reviewsCount > 0 ? round($reviewsAvg, 1) : null,
                'reviews_count' => $reviewsCount,
            ],
        ]);
    }

    /**
     * Full product catalog, paginated — the "Cały sklep"/"Zobacz wszystkie"
     * links under Bestsellers/New Arrivals/Featured Products on the
     * homepage all pointed at route('tenant.shop') itself (this controller's
     * own index()), which only ever renders curated homepage sections
     * (max 8-12 items each), never a real full listing. Clicking any of
     * those CTAs just reloaded the same homepage the visitor was already
     * on — there was no "browse everything" page anywhere in the app to
     * send them to instead.
     */
    public function allProducts(Request $request)
    {
        $query = Product::published()->with(['images', 'category']);

        if ($request->category) {
            $category = Category::where('slug', $request->category)->first();
            if ($category) {
                $categoryIds = [$category->id, ...$category->children->pluck('id')->toArray()];
                $query->whereIn('category_id', $categoryIds);
            }
        }
        if ($request->min_price) {
            $query->where('price', '>=', $request->min_price);
        }
        if ($request->max_price) {
            $query->where('price', '<=', $request->max_price);
        }
        if ($request->in_stock) {
            $query->inStock();
        }
        if ($request->type) {
            $query->where('type', $request->type);
        }

        match ($request->sort) {
            'price_asc' => $query->orderBy('price'),
            'price_desc' => $query->orderByDesc('price'),
            'newest' => $query->latest(),
            'rating' => $query->orderByDesc('reviews_avg'),
            'bestsellers' => $query->orderByDesc('sales_count'),
            default => $query->orderBy('sort_order'),
        };

        $products = $query->paginate(24)->withQueryString();

        $categories = Category::active()->roots()->ordered()->get(['id', 'name', 'slug']);

        return Inertia::render('Tenant/Client/Shop/Products', [
            'products' => $products,
            'categories' => $categories,
            'filters' => $request->only(['category', 'min_price', 'max_price', 'in_stock', 'type', 'sort']),
        ]);
    }

    public function search(Request $request)
    {
        $query = $request->get('q', '');

        $products = Product::published()
            ->inStock()
            ->where(function ($q) use ($query) {
                $fulltextTerm = $this->fulltextBooleanTerm($query);
                if ($fulltextTerm !== null) {
                    $q->whereFullText(['name', 'short_description'], $fulltextTerm, ['mode' => 'boolean']);
                } else {
                    $q->where('name', 'like', "%{$query}%")
                        ->orWhere('short_description', 'like', "%{$query}%");
                }
                $q->orWhere('sku', 'like', "%{$query}%");
            })
            ->with(['images', 'category'])
            ->paginate(24)
            ->withQueryString();

        return Inertia::render('Tenant/Client/Shop/Search', [
            'products' => $products,
            'query' => $query,
        ]);
    }

    public function searchSuggest(Request $request)
    {
        $q = trim($request->get('q', ''));

        if (strlen($q) < 2) {
            return response()->json([]);
        }

        $products = Product::published()
            ->inStock()
            ->where(function ($query) use ($q) {
                $fulltextTerm = $this->fulltextBooleanTerm($q);
                if ($fulltextTerm !== null) {
                    $query->whereFullText(['name', 'short_description'], $fulltextTerm, ['mode' => 'boolean']);
                } else {
                    $query->where('name', 'like', "%{$q}%");
                }
                $query->orWhere('sku', 'like', "%{$q}%");
            })
            ->with(['images' => fn ($i) => $i->orderBy('sort_order')->limit(1)])
            ->select(['id', 'name', 'slug', 'price', 'compare_price'])
            ->limit(6)
            ->get()
            ->map(fn ($p) => [
                'id' => $p->id,
                'name' => $p->name,
                'slug' => $p->slug,
                'price' => $p->price,
                'compare_price' => $p->compare_price,
                'image' => $p->images->first()?->url,
            ]);

        return response()->json($products);
    }

    /**
     * Turns free-text user input into a MySQL boolean-mode FULLTEXT search term
     * with trailing wildcards for prefix matching (so typing "koszu" starts
     * matching "koszula" the way an autocomplete needs to). Strips characters
     * that have special meaning in boolean mode so user input can't be used to
     * build unintended query operators. Returns null (caller falls back to
     * LIKE) for very short queries, where FULLTEXT's minimum word length would
     * otherwise silently return nothing.
     */
    private function fulltextBooleanTerm(string $query): ?string
    {
        $words = preg_split('/\s+/', trim(preg_replace('/[+\-<>()~*"@]+/', ' ', $query)), -1, PREG_SPLIT_NO_EMPTY);

        if (empty($words)) {
            return null;
        }

        $terms = array_filter(array_map(fn ($w) => mb_strlen($w) >= 3 ? '+' . $w . '*' : null, $words));

        return empty($terms) ? null : implode(' ', $terms);
    }
}
