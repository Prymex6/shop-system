<?php

namespace App\Http\Controllers\Tenant\Manager;

use App\Http\Controllers\Controller;
use App\Models\Tenant\Order;
use App\Services\PickingListService;
use Illuminate\Http\Request;

class PickingListController extends Controller
{
    public function __construct(protected PickingListService $pickingListService) {}

    public function generate(Request $request)
    {
        $request->validate([
            'order_ids' => 'required|array|min:1',
            'order_ids.*' => 'integer|exists:orders,id',
        ]);

        $orders = Order::with('items.product', 'items.variant')
            ->whereIn('id', $request->order_ids)
            ->get();

        $html = $this->pickingListService->exportPdf($orders);

        return response($html)->header('Content-Type', 'text/html; charset=UTF-8');
    }
}
