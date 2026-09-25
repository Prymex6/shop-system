<?php

namespace App\Http\Controllers\Tenant\Manager;

use App\Http\Controllers\Controller;
use App\Models\Tenant\Product;
use App\Models\Tenant\StockMovement;
use App\Services\InventoryService;
use App\Support\Csv;
use Illuminate\Http\Request;
use Inertia\Inertia;

class InventoryController extends Controller
{
    public function __construct(private InventoryService $inventoryService) {}

    public function index(Request $request)
    {
        $products = Product::where('track_stock', true)
            ->with('category')
            ->when($request->search, fn ($q) => $q->where('name', 'like', '%' . $request->search . '%'))
            ->when($request->filter === 'low', fn ($q) => $q->lowStock())
            ->when($request->filter === 'out', fn ($q) => $q->where('stock_quantity', 0))
            ->orderBy('stock_quantity')
            ->paginate(30)
            ->withQueryString();

        return Inertia::render('Tenant/Manager/Inventory/Index', [
            'products' => $products,
            'filters' => $request->only(['search', 'filter']),
        ]);
    }

    public function adjust(Request $request, Product $product)
    {
        $data = $request->validate([
            'quantity_change' => 'required|integer|not_in:0',
            'type' => 'required|in:adjustment,import,damage,return',
            'reason' => 'nullable|string|max:300',
        ]);

        $this->inventoryService->adjustProduct(
            $product,
            $data['quantity_change'],
            $data['type'],
            $data['reason'] ?? null
        );

        return back()->with('success', __('messages.stock_updated'));
    }

    public function movements(Product $product)
    {
        $movements = StockMovement::where('product_id', $product->id)
            ->latest()
            ->paginate(30);

        return Inertia::render('Tenant/Manager/Inventory/Movements', [
            'product' => $product,
            'movements' => $movements,
        ]);
    }

    public function export(Request $request)
    {
        $products = Product::where('track_stock', true)
            ->with('category')
            ->get(['id', 'name', 'sku', 'stock_quantity', 'low_stock_threshold']);

        $csv = __('messages.csv_inventory_header') . "\n";
        foreach ($products as $p) {
            $csv .= implode(',', [
                $p->id,
                Csv::field($p->name),
                Csv::field($p->sku ?? ''),
                Csv::field($p->category?->name ?? ''),
                $p->stock_quantity,
                $p->low_stock_threshold,
            ]) . "\n";
        }

        return response($csv, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="inventory-' . date('Y-m-d') . '.csv"',
        ]);
    }
}
