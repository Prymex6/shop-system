<?php

namespace App\Http\Controllers\Tenant\Staff;

use App\Http\Controllers\Controller;
use App\Models\Tenant\Order;
use App\Services\FulfillmentService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class FulfillmentStaffController extends Controller
{
    public function __construct(private FulfillmentService $fulfillment) {}

    public function index()
    {
        $orders = Order::with(['items.product', 'customer', 'shippingMethod'])
            ->whereIn('payment_status', ['paid'])
            ->whereIn('fulfillment_status', ['unfulfilled', 'processing'])
            ->latest()
            ->get();

        return Inertia::render('Tenant/Staff/Fulfillment', [
            'orders' => $orders,
        ]);
    }

    public function updateStatus(Request $request, Order $order)
    {
        $data = $request->validate([
            'fulfillment_status' => 'required|in:processing,shipped,delivered,cancelled',
            'item_ids' => 'nullable|array',
            'item_ids.*' => 'integer|exists:order_items,id',
        ]);

        $order = $this->fulfillment->updateStatus($order, $data['fulfillment_status'], $data['item_ids'] ?? null);

        return response()->json(['ok' => true, 'order' => $order->load('items')]);
    }
}
