<?php

namespace App\Http\Controllers\Tenant\Manager;

use App\Http\Controllers\Controller;
use App\Models\Tenant\Product;
use App\Models\Tenant\ProductWarehouseStock;
use App\Models\Tenant\Warehouse;
use App\Services\MultiWarehouseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class WarehouseController extends Controller
{
    public function __construct(protected MultiWarehouseService $warehouseService) {}

    public function index()
    {
        $warehouses = Warehouse::withCount('stock')
            ->orderBy('name')
            ->get();

        return Inertia::render('Tenant/Manager/Warehouses/Index', [
            'warehouses' => $warehouses,
        ]);
    }

    public function store(Request $request)
    {
        // Plan feature "multi_warehouse" (LandlordSeeder — off on Starter/
        // Basic) was never checked anywhere: a plan not paying for it could
        // still create as many warehouses as a Pro/Premium tenant. Only the
        // 2nd+ warehouse is gated — every plan gets one for free, matching
        // how a fresh tenant already starts with a default warehouse. A
        // lookup failure never blocks creation.
        try {
            $plan = tenancy()->tenant?->plan;
            if ($plan && !$plan->hasFeature('multi_warehouse') && Warehouse::count() >= 1) {
                return back()->withErrors(['name' => __('messages.warehouses_not_in_plan')]);
            }
        } catch (\Exception $e) {
            Log::warning('Plan multi_warehouse feature check failed, allowing creation: ' . $e->getMessage());
        }

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'city' => 'required|string|max:100',
            'address' => 'required|string|max:500',
            'is_active' => 'boolean',
        ]);

        Warehouse::create($data);

        return back()->with('success', __('messages.warehouse_created'));
    }

    public function update(Request $request, Warehouse $warehouse)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'city' => 'required|string|max:100',
            'address' => 'required|string|max:500',
            'is_active' => 'boolean',
        ]);

        $warehouse->update($data);

        return back()->with('success', __('messages.warehouse_updated'));
    }

    public function destroy(Warehouse $warehouse)
    {
        $warehouse->delete();

        return back()->with('success', __('messages.warehouse_deleted'));
    }

    public function stock(Warehouse $warehouse)
    {
        $stockItems = ProductWarehouseStock::with(['product', 'variant'])
            ->where('warehouse_id', $warehouse->id)
            ->get();

        return Inertia::render('Tenant/Manager/Warehouses/Stock', [
            'warehouse' => $warehouse,
            'stockItems' => $stockItems,
            'otherWarehouses' => Warehouse::where('id', '!=', $warehouse->id)->where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function adjustStock(Request $request, Warehouse $warehouse)
    {
        $data = $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|integer',
            'reason' => 'nullable|string|max:255',
        ]);

        $product = Product::findOrFail($data['product_id']);
        $this->warehouseService->adjustStock($warehouse->id, $product, $data['quantity'], $data['reason'] ?? 'manual adjustment');

        return back()->with('success', 'Stan magazynowy zaktualizowany.');
    }

    public function transferStock(Request $request, Warehouse $warehouse)
    {
        $data = $request->validate([
            // Service layer rejects from === to explicitly (can't express a
            // route-model-bound param as a Laravel 'different:' target here).
            'to_warehouse_id' => 'required|exists:warehouses,id',
            'product_id' => 'required|exists:products,id',
            'variant_id' => 'nullable|exists:product_variants,id',
            'quantity' => 'required|integer|min:1',
            'reason' => 'nullable|string|max:255',
        ]);

        $product = Product::findOrFail($data['product_id']);

        $this->warehouseService->transferStock(
            fromWarehouseId: $warehouse->id,
            toWarehouseId: (int) $data['to_warehouse_id'],
            product: $product,
            quantity: $data['quantity'],
            variantId: $data['variant_id'] ?? null,
            reason: $data['reason'] ?? null,
            createdBy: Auth::guard('tenant')->id(),
        );

        return back()->with('success', __('messages.stock_transferred'));
    }
}
