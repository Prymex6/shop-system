<?php

namespace App\Http\Controllers\Tenant\Manager;

use App\Http\Controllers\Controller;
use App\Models\Tenant\VolumeDiscount;
use Illuminate\Http\Request;
use Inertia\Inertia;

class VolumeDiscountController extends Controller
{
    public function index(Request $request)
    {
        $discounts = VolumeDiscount::with(['product', 'category'])
            ->orderBy('min_quantity')
            ->paginate(25)
            ->withQueryString();

        return Inertia::render('Tenant/Manager/VolumeDiscounts/Index', [
            'volumeDiscounts' => $discounts,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'product_id' => 'nullable|exists:products,id',
            'category_id' => 'nullable|exists:categories,id',
            'min_quantity' => 'required|integer|min:1',
            'discount_type' => 'required|in:percentage,fixed',
            'discount_value' => 'required|numeric|min:0',
            'is_active' => 'boolean',
        ]);

        if (empty($data['product_id']) && empty($data['category_id'])) {
            return back()->withErrors(['product_id' => 'Podaj produkt lub kategorię.']);
        }

        VolumeDiscount::create($data);

        return back()->with('success', 'Rabat wolumenowy utworzony.');
    }

    public function update(Request $request, VolumeDiscount $volumeDiscount)
    {
        $data = $request->validate([
            'product_id' => 'nullable|exists:products,id',
            'category_id' => 'nullable|exists:categories,id',
            'min_quantity' => 'required|integer|min:1',
            'discount_type' => 'required|in:percentage,fixed',
            'discount_value' => 'required|numeric|min:0',
            'is_active' => 'boolean',
        ]);

        $volumeDiscount->update($data);

        return back()->with('success', 'Rabat wolumenowy zaktualizowany.');
    }

    public function destroy(VolumeDiscount $volumeDiscount)
    {
        $volumeDiscount->delete();

        return back()->with('success', __('messages.volume_discount_deleted'));
    }
}
