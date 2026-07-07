<?php

namespace App\Http\Controllers\Tenant\Manager;

use App\Http\Controllers\Controller;
use App\Models\Tenant\FraudBlocklist;
use App\Models\Tenant\Order;
use App\Services\AuditService;
use App\Services\OrderCancellationService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class FraudController extends Controller
{
    public function __construct(private OrderCancellationService $cancellationService) {}

    public function index()
    {
        $flaggedOrders = Order::whereHas('fraudFlags')
            ->with(['fraudFlags', 'customer'])
            ->withCount('fraudFlags')
            ->latest()
            ->paginate(25);

        return Inertia::render('Tenant/Manager/Fraud/Index', [
            'flaggedOrders' => $flaggedOrders,
            'blocklist' => FraudBlocklist::latest()->get(),
        ]);
    }

    public function show(Order $order)
    {
        $order->load(['fraudFlags', 'items.product', 'customer']);

        return Inertia::render('Tenant/Manager/Fraud/Show', [
            'order' => $order,
        ]);
    }

    public function release(Order $order)
    {
        // Release order from review — change status to processing
        if ($order->status === 'pending') {
            $order->update(['status' => 'processing']);
        }

        AuditService::log('fraud.order_released', $order);

        return back()->with('success', __('messages.order_released'));
    }

    public function block(Order $order)
    {
        // Goes through the same cancellation path as the manager order-status
        // screen — a real gateway refund attempt, stock restoration, loyalty
        // revocation, and the status-transition guard — instead of writing
        // status=cancelled/payment_status=refunded directly, which previously
        // let this fabricate a "refund" that never actually happened at the
        // payment gateway and never restored the stock it had reserved.
        $result = $this->cancellationService->cancel($order);

        if (!$result['success']) {
            return back()->with('error', $result['message']);
        }

        // Add email to blocklist
        FraudBlocklist::firstOrCreate(
            ['type' => 'email', 'value' => strtolower($order->customer_email)],
            ['reason' => 'Manual block from fraud review']
        );

        AuditService::log('fraud.order_blocked', $order);

        return back()->with('success', __('messages.order_cancelled_email_blocked'));
    }

    // Blocklist CRUD
    public function blocklistIndex()
    {
        return response()->json(FraudBlocklist::latest()->get());
    }

    public function blocklistStore(Request $request)
    {
        $data = $request->validate([
            'type' => 'required|in:email,ip,card_bin',
            'value' => 'required|string|max:255',
            'reason' => 'nullable|string|max:500',
        ]);

        $entry = FraudBlocklist::create($data);

        AuditService::log('fraud.blocklist_added', $entry, [], $data);

        return response()->json(['entry' => $entry], 201);
    }

    public function blocklistDestroy(FraudBlocklist $entry)
    {
        AuditService::log('fraud.blocklist_removed', $entry, $entry->only(['type', 'value', 'reason']));

        $entry->delete();

        return response()->json(['ok' => true]);
    }
}
