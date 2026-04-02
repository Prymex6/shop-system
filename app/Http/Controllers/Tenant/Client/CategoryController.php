<?php

namespace App\Http\Controllers\Tenant\Client;

use App\Http\Controllers\Controller;
use App\Models\Tenant\Category;
use App\Models\Tenant\Product;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CategoryController extends Controller
{
    public function show(Request $request, Category $category)
    {
        if (!$category->is_active) {
            abort(404);
        }

        $categoryIds = [$category->id, ...$category->children->pluck('id')->toArray()];

        $query = Product::published()
            ->whereIn('category_id', $categoryIds)
            ->with(['images', 'category']);

        // Filters
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

        // Sorting
        match ($request->sort) {
            'price_asc' => $query->orderBy('price'),
            'price_desc' => $query->orderByDesc('price'),
            'newest' => $query->latest(),
            'rating' => $query->orderByDesc('reviews_avg'),
            'bestsellers' => $query->orderByDesc('sales_count'),
            default => $query->orderBy('sort_order'),
        };

        $products = $query->paginate(24)->withQueryString();

        return Inertia::render('Tenant/Client/Shop/Category', [
            'category' => $category->load('parent', 'children'),
            'products' => $products,
            'filters' => $request->only(['min_price', 'max_price', 'in_stock', 'type', 'sort']),
        ]);
    }
}
