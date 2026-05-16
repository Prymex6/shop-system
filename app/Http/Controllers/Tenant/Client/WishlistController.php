<?php

namespace App\Http\Controllers\Tenant\Client;

use App\Http\Controllers\Controller;
use App\Models\Tenant\Product;
use App\Models\Tenant\Wishlist;
use Illuminate\Http\Request;
use Inertia\Inertia;

class WishlistController extends Controller
{
    public function index()
    {
        $customer = auth('customer')->user();

        $items = Wishlist::where('customer_id', $customer->id)
            ->with(['product.images', 'product.category'])
            ->latest()
            ->get();

        return Inertia::render('Tenant/Client/Wishlist', [
            'items' => $items,
        ]);
    }

    public function toggle(Request $request, Product $product)
    {
        $customer = auth('customer')->user();

        $existing = Wishlist::where('customer_id', $customer->id)
            ->where('product_id', $product->id)
            ->first();

        if ($existing) {
            $existing->delete();
            $added = false;
        } else {
            Wishlist::create([
                'customer_id' => $customer->id,
                'product_id' => $product->id,
                'price_at_added' => $product->price,
                'was_out_of_stock' => !$product->isInStock(),
            ]);
            $added = true;
        }

        if ($request->wantsJson()) {
            return response()->json(['added' => $added]);
        }

        return back()->with('success', $added ? 'Dodano do listy życzeń.' : 'Usunięto z listy życzeń.');
    }
}
