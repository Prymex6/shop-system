<?php

namespace App\Http\Controllers\Tenant\Manager;

use App\Http\Controllers\Controller;
use App\Models\Tenant\Product;
use App\Models\Tenant\PurchaseOrder;
use App\Models\Tenant\Supplier;
use App\Services\PurchaseOrderService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PurchaseOrderController extends Controller
{
    public function __construct(protected PurchaseOrderService $poService) {}

    public function index(Request $request)
    {
        $orders = PurchaseOrder::with('supplier')
            ->when($request->status, fn ($q) => $q->where('status', $request->status))
            ->when($request->supplier, fn ($q) => $q->where('supplier_id', $request->supplier))
            ->latest()
            ->paginate(25)
            ->withQueryString();

        $suppliers = Supplier::active()->orderBy('name')->get(['id', 'name']);

        return Inertia::render('Tenant/Manager/PurchaseOrders/Index', [
            'purchaseOrders' => $orders,
            'suppliers' => $suppliers,
            'filters' => $request->only(['status', 'supplier']),
        ]);
    }

    public function create()
    {
        $suppliers = Supplier::active()->orderBy('name')->get(['id', 'name', 'company_name']);
        $products = Product::select('id', 'name', 'sku', 'price')->orderBy('name')->get();

        return Inertia::render('Tenant/Manager/PurchaseOrders/Create', [
            'suppliers' => $suppliers,
            'products' => $products,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'supplier_id' => 'required|exists:suppliers,id',
            'notes' => 'nullable|string',
            'expected_at' => 'nullable|date',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'nullable|exists:products,id',
            'items.*.variant_id' => 'nullable|exists:product_variants,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.unit_cost' => 'required|numeric|min:0',
        ]);

        $supplier = Supplier::findOrFail($data['supplier_id']);
        $po = $this->poService->create($supplier, $data);

        return redirect()->route('tenant.manager.purchase-orders.index')
            ->with('success', __('messages.purchase_order_created', ['number' => $po->po_number]));
    }

    public function show(PurchaseOrder $purchaseOrder)
    {
        return Inertia::render('Tenant/Manager/PurchaseOrders/Show', [
            'order' => $purchaseOrder->load('supplier', 'items.product', 'items.variant'),
        ]);
    }

    public function update(Request $request, PurchaseOrder $purchaseOrder)
    {
        $data = $request->validate([
            // 'partial'/'received' must only ever be set by receive(), which is what
            // actually credits stock — allowing them here let a PO display as fully
            // received while stock_quantity was never incremented at all.
            'status' => 'sometimes|in:draft,sent,confirmed,cancelled',
            'notes' => 'nullable|string',
            'expected_at' => 'nullable|date',
        ]);

        $purchaseOrder->update($data);

        return back()->with('success', __('messages.order_updated'));
    }

    public function receive(Request $request, PurchaseOrder $purchaseOrder)
    {
        $data = $request->validate([
            'items' => 'required|array',
            'items.*.id' => 'required|integer|exists:purchase_order_items,id',
            'items.*.received_quantity' => 'required|integer|min:0',
        ]);

        $this->poService->receive($purchaseOrder, $data['items']);

        return back()->with('success', __('messages.goods_receipt_saved'));
    }

    public function destroy(PurchaseOrder $purchaseOrder)
    {
        if (!in_array($purchaseOrder->status, ['draft', 'cancelled'])) {
            return back()->with('error', __('messages.only_draft_orders_deletable'));
        }

        $purchaseOrder->delete();

        return back()->with('success', __('messages.purchase_order_deleted'));
    }
}
