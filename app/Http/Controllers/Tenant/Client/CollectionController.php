<?php

namespace App\Http\Controllers\Tenant\Client;

use App\Http\Controllers\Controller;
use App\Models\Tenant\Collection;
use Inertia\Inertia;

class CollectionController extends Controller
{
    public function index()
    {
        $collections = Collection::active()
            ->orderBy('sort_order')
            ->get();

        return Inertia::render('Tenant/Client/Collections/Index', [
            'collections' => $collections,
        ]);
    }

    public function show(string $slug)
    {
        $collection = Collection::where('slug', $slug)
            ->active()
            ->firstOrFail();

        if (!$collection->isCurrentlyActive()) {
            abort(404);
        }

        $products = $collection->products()
            ->where('is_published', true)
            ->with(['images', 'category'])
            ->paginate(24)
            ->withQueryString();

        return Inertia::render('Tenant/Client/Collections/Show', [
            'collection' => $collection,
            'products' => $products,
        ]);
    }
}
