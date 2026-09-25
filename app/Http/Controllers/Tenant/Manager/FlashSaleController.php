<?php

namespace App\Http\Controllers\Tenant\Manager;

use App\Http\Controllers\Controller;
use App\Models\Tenant\FlashSale;
use Illuminate\Http\Request;
use Inertia\Inertia;

class FlashSaleController extends Controller
{
    public function index(Request $request)
    {
        $sales = FlashSale::withCount('products')
            ->orderBy('starts_at', 'desc')
            ->paginate(25)
            ->withQueryString();

        return Inertia::render('Tenant/Manager/FlashSales/Index', [
            'flashSales' => $sales,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'discount_type' => 'required|in:percentage,fixed',
            'discount_value' => 'required|numeric|min:0',
            'starts_at' => 'required|date',
            'ends_at' => 'required|date|after:starts_at',
            'max_orders' => 'nullable|integer|min:1',
            'is_active' => 'boolean',
        ]);

        FlashSale::create($data);

        return back()->with('success', __('messages.flash_sale_created'));
    }

    public function update(Request $request, FlashSale $flashSale)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'discount_type' => 'required|in:percentage,fixed',
            'discount_value' => 'required|numeric|min:0',
            'starts_at' => 'required|date',
            'ends_at' => 'required|date|after:starts_at',
            'max_orders' => 'nullable|integer|min:1',
            'is_active' => 'boolean',
        ]);

        $flashSale->update($data);

        return back()->with('success', __('messages.flash_sale_updated'));
    }

    public function destroy(FlashSale $flashSale)
    {
        $flashSale->delete();

        return back()->with('success', __('messages.flash_sale_deleted'));
    }

    public function addProducts(Request $request, FlashSale $flashSale)
    {
        $data = $request->validate([
            'products' => 'required|array',
            'products.*.product_id' => 'required|exists:products,id',
            'products.*.custom_price' => 'nullable|numeric|min:0',
        ]);

        $sync = [];
        foreach ($data['products'] as $item) {
            $sync[$item['product_id']] = ['custom_price' => $item['custom_price'] ?? null];
        }

        $flashSale->products()->syncWithoutDetaching($sync);

        return back()->with('success', __('messages.products_added_to_flash_sale'));
    }

    public function removeProduct(Request $request, FlashSale $flashSale)
    {
        $data = $request->validate([
            'product_id' => 'required|exists:products,id',
        ]);

        $flashSale->products()->detach($data['product_id']);

        return back()->with('success', __('messages.product_removed_from_flash_sale'));
    }

    /**
     * Replace the full product list for this flash sale — the "Produkty"
     * checkbox modal represents the complete desired state, unlike
     * addProducts()/removeProduct() above which are additive/single-item.
     */
    public function syncProducts(Request $request, FlashSale $flashSale)
    {
        $data = $request->validate([
            'product_ids' => 'required|array',
            'product_ids.*' => 'exists:products,id',
        ]);

        $flashSale->products()->sync($data['product_ids']);

        return back()->with('success', __('messages.flash_sale_products_updated'));
    }
}
