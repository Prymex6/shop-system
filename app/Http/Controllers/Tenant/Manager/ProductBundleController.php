<?php

namespace App\Http\Controllers\Tenant\Manager;

use App\Http\Controllers\Controller;
use App\Models\Tenant\Product;
use App\Models\Tenant\ProductBundle;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;

class ProductBundleController extends Controller
{
    public function index()
    {
        $bundles = ProductBundle::with('items.product')->latest()->paginate(25);
        $products = Product::select('id', 'name')->where('is_published', true)->orderBy('name')->get();

        return Inertia::render('Tenant/Manager/ProductBundles/Index', [
            'bundles' => $bundles,
            'products' => $products,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'unique:product_bundles,slug'],
            'description' => ['nullable', 'string'],
            'price' => ['required', 'numeric', 'min:0'],
            'compare_price' => ['nullable', 'numeric', 'min:0'],
            'is_active' => ['boolean'],
            'image' => ['nullable', 'string', 'max:500'],
            'items' => ['nullable', 'array'],
            'items.*.product_id' => ['required', 'integer', 'exists:products,id'],
            'items.*.variant_id' => ['nullable', 'integer', 'exists:product_variants,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
        ]);

        if (empty($data['slug'])) {
            $data['slug'] = Str::slug($data['name']);
        }

        $items = $data['items'] ?? [];
        unset($data['items']);

        $bundle = ProductBundle::create($data);

        foreach ($items as $item) {
            $bundle->items()->create([
                'product_id' => $item['product_id'],
                'variant_id' => $item['variant_id'] ?? null,
                'quantity' => $item['quantity'],
            ]);
        }

        return back()->with('success', __('messages.bundle_created'));
    }

    public function update(Request $request, ProductBundle $productBundle)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'unique:product_bundles,slug,' . $productBundle->id],
            'description' => ['nullable', 'string'],
            'price' => ['required', 'numeric', 'min:0'],
            'compare_price' => ['nullable', 'numeric', 'min:0'],
            'is_active' => ['boolean'],
            'image' => ['nullable', 'string', 'max:500'],
            'items' => ['nullable', 'array'],
            'items.*.product_id' => ['required', 'integer', 'exists:products,id'],
            'items.*.variant_id' => ['nullable', 'integer', 'exists:product_variants,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
        ]);

        if (empty($data['slug'])) {
            $data['slug'] = Str::slug($data['name']);
        }

        $items = $data['items'] ?? [];
        unset($data['items']);

        $productBundle->update($data);

        // Replace items
        $productBundle->items()->delete();
        foreach ($items as $item) {
            $productBundle->items()->create([
                'product_id' => $item['product_id'],
                'variant_id' => $item['variant_id'] ?? null,
                'quantity' => $item['quantity'],
            ]);
        }

        return back()->with('success', __('messages.bundle_updated'));
    }

    public function destroy(ProductBundle $productBundle)
    {
        $productBundle->items()->delete();
        $productBundle->delete();

        return redirect()->route('tenant.manager.bundles.index')->with('success', __('messages.bundle_deleted'));
    }
}
