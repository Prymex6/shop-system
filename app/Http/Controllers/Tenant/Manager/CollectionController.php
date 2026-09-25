<?php

namespace App\Http\Controllers\Tenant\Manager;

use App\Http\Controllers\Controller;
use App\Models\Tenant\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;

class CollectionController extends Controller
{
    public function index(Request $request)
    {
        $collections = Collection::withCount('products')
            ->orderBy('sort_order')
            ->paginate(25)
            ->withQueryString();

        return Inertia::render('Tenant/Manager/Collections/Index', [
            'collections' => $collections,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:collections,slug',
            'description' => 'nullable|string',
            'image' => 'nullable|string|max:500',
            'is_active' => 'boolean',
            'sort_order' => 'integer|min:0',
            'starts_at' => 'nullable|date',
            'ends_at' => 'nullable|date|after:starts_at',
        ]);

        if (empty($data['slug'])) {
            $data['slug'] = Str::slug($data['name']);
        }

        $collection = Collection::create($data);

        return back()->with('success', __('messages.collection_created'));
    }

    public function update(Request $request, Collection $collection)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:collections,slug,' . $collection->id,
            'description' => 'nullable|string',
            'image' => 'nullable|string|max:500',
            'is_active' => 'boolean',
            'sort_order' => 'integer|min:0',
            'starts_at' => 'nullable|date',
            'ends_at' => 'nullable|date',
        ]);

        $collection->update($data);

        return back()->with('success', __('messages.collection_updated'));
    }

    public function destroy(Collection $collection)
    {
        $collection->delete();

        return back()->with('success', __('messages.collection_deleted'));
    }

    public function addProduct(Request $request, Collection $collection)
    {
        $data = $request->validate([
            'product_id' => 'required|exists:products,id',
            'sort_order' => 'integer|min:0',
        ]);

        $collection->products()->syncWithoutDetaching([
            $data['product_id'] => ['sort_order' => $data['sort_order'] ?? 0],
        ]);

        return back()->with('success', __('messages.product_added_to_collection'));
    }

    public function removeProduct(Request $request, Collection $collection)
    {
        $data = $request->validate([
            'product_id' => 'required|exists:products,id',
        ]);

        $collection->products()->detach($data['product_id']);

        return back()->with('success', __('messages.product_removed_from_collection'));
    }

    public function reorder(Request $request, Collection $collection)
    {
        $data = $request->validate([
            'products' => 'required|array',
            'products.*.id' => 'required|integer',
            'products.*.sort_order' => 'required|integer|min:0',
        ]);

        foreach ($data['products'] as $item) {
            $collection->products()->updateExistingPivot($item['id'], ['sort_order' => $item['sort_order']]);
        }

        return back()->with('success', __('messages.order_of_items_updated'));
    }
}
