<?php

namespace App\Http\Controllers\Tenant\Manager;

use App\Http\Controllers\Controller;
use App\Models\Tenant\ShippingMethod;
use App\Models\Tenant\ShippingZone;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ShippingController extends Controller
{
    public function index()
    {
        return Inertia::render('Tenant/Manager/Shipping/Index', [
            'methods' => ShippingMethod::orderBy('sort_order')->get(),
        ]);
    }

    public function storeMethod(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:150',
            'description' => 'nullable|string|max:300',
            'type' => 'required|in:standard,express,pickup,digital',
            'carrier' => 'nullable|in:inpost',
            'price' => 'required|numeric|min:0',
            'free_from' => 'nullable|numeric|min:0',
            'delivery_days_min' => 'required|integer|min:0',
            'delivery_days_max' => 'required|integer|min:0',
            'weight_min' => 'nullable|numeric|min:0',
            'weight_max' => 'nullable|numeric|min:0',
            'is_active' => 'boolean',
        ]);

        ShippingMethod::create($data);

        return back()->with('success', __('messages.shipping_method_added'));
    }

    public function updateMethod(Request $request, ShippingMethod $method)
    {
        $data = $request->validate([
            'name' => 'required|string|max:150',
            'description' => 'nullable|string|max:300',
            'type' => 'required|in:standard,express,pickup,digital',
            'carrier' => 'nullable|in:inpost',
            'price' => 'required|numeric|min:0',
            'free_from' => 'nullable|numeric|min:0',
            'delivery_days_min' => 'required|integer|min:0',
            'delivery_days_max' => 'required|integer|min:0',
            'weight_min' => 'nullable|numeric|min:0',
            'weight_max' => 'nullable|numeric|min:0',
            'is_active' => 'boolean',
        ]);

        $method->update($data);

        return back()->with('success', __('messages.shipping_method_updated'));
    }

    public function destroyMethod(ShippingMethod $method)
    {
        $method->delete();

        return back()->with('success', __('messages.shipping_method_deleted'));
    }

    public function toggleMethod(ShippingMethod $method)
    {
        $method->update(['is_active' => !$method->is_active]);

        return back()->with('success', $method->is_active ? 'Aktywowana.' : 'Dezaktywowana.');
    }

    public function zones()
    {
        return Inertia::render('Tenant/Manager/Shipping/Zones', [
            'zones' => ShippingZone::with('methods')->get(),
            'methods' => ShippingMethod::where('is_active', true)->orderBy('sort_order')->get(),
        ]);
    }

    public function storeZone(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:150',
            'countries' => 'required|array|min:1',
            'countries.*' => 'string|size:2',
            'is_default' => 'boolean',
            'methods' => 'nullable|array',
        ]);

        if (!empty($data['is_default'])) {
            ShippingZone::where('is_default', true)->update(['is_default' => false]);
        }

        $zone = ShippingZone::create($data);

        if (!empty($data['methods'])) {
            $zone->methods()->sync(
                collect($data['methods'])->mapWithKeys(fn ($m) => [
                    $m['id'] => ['price_override' => $m['price_override'] ?? null, 'free_from_override' => $m['free_from_override'] ?? null],
                ])->toArray()
            );
        }

        return back()->with('success', __('messages.shipping_zone_added'));
    }

    public function updateZone(Request $request, ShippingZone $zone)
    {
        $data = $request->validate([
            'name' => 'required|string|max:150',
            'countries' => 'required|array|min:1',
            'countries.*' => 'string|size:2',
            'is_default' => 'boolean',
            'methods' => 'nullable|array',
        ]);

        if (!empty($data['is_default'])) {
            ShippingZone::where('is_default', true)->where('id', '!=', $zone->id)->update(['is_default' => false]);
        }

        $zone->update($data);

        if (isset($data['methods'])) {
            $zone->methods()->sync(
                collect($data['methods'])->mapWithKeys(fn ($m) => [
                    $m['id'] => ['price_override' => $m['price_override'] ?? null, 'free_from_override' => $m['free_from_override'] ?? null],
                ])->toArray()
            );
        }

        return back()->with('success', __('messages.shipping_zone_updated'));
    }

    public function destroyZone(ShippingZone $zone)
    {
        $zone->delete();

        return back()->with('success', __('messages.shipping_zone_deleted'));
    }
}
