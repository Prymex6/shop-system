<?php

namespace App\Http\Controllers\Tenant\Client;

use App\Http\Controllers\Controller;
use App\Models\Tenant\Order;
use App\Models\Tenant\RmaRequest;
use App\Services\RmaService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class RmaController extends Controller
{
    // Matches the statutory EU consumer-rights withdrawal window this shop's
    // own legal pages (Regulamin, seeded by InstallController) cite — RMA
    // requests previously had no deadline check at all, letting a return be
    // requested (and approved) on an order from a year ago.
    private const RETURN_WINDOW_DAYS = 14;

    public function __construct(protected RmaService $rmaService) {}

    private function returnDeadline(Order $order): ?Carbon
    {
        $reference = $order->delivered_at ?? $order->shipped_at;

        return $reference?->copy()->addDays(self::RETURN_WINDOW_DAYS);
    }

    /**
     * Resolve an order by order_number and verify the requester is allowed
     * to file/view an RMA for it — either as the order's own logged-in
     * customer, or via the same tracking_token guests already use to view
     * their order (OrderTrackingController/PaymentController). Without the
     * token path, guests — who make up most orders placed from anonymous
     * TikTok-ad traffic — had no way to ever request a return.
     */
    private function resolveOrder(Request $request, string $orderNumber): Order
    {
        $order = Order::where('order_number', $orderNumber)->firstOrFail();

        $token = (string) $request->query('token', $request->input('token', ''));
        $tokenValid = $token !== '' && $order->tracking_token && hash_equals($order->tracking_token, $token);

        $customer = Auth::guard('customer')->user();
        $isOwner = $customer && $order->customer_id && $customer->id === $order->customer_id;

        abort_unless($tokenValid || $isOwner, 403);

        return $order;
    }

    public function create(Request $request, string $orderNumber)
    {
        $order = $this->resolveOrder($request, $orderNumber);
        $order->load('items');

        $deadline = $this->returnDeadline($order);

        return Inertia::render('Tenant/Client/Rma/Create', [
            'order' => $order,
            'token' => (string) $request->query('token', ''),
            'already_requested' => RmaRequest::where('order_id', $order->id)
                ->whereIn('status', ['pending', 'approved', 'received', 'inspected'])
                ->exists(),
            'return_deadline' => $deadline?->toDateString(),
            'return_window_expired' => $deadline !== null && $deadline->isPast(),
        ]);
    }

    public function store(Request $request, string $orderNumber)
    {
        $order = $this->resolveOrder($request, $orderNumber);

        // Prevent duplicate RMA for same order
        if (RmaRequest::where('order_id', $order->id)->whereIn('status', ['pending', 'approved', 'received', 'inspected'])->exists()) {
            return back()->with('error', __('messages.rma_already_exists'));
        }

        $deadline = $this->returnDeadline($order);
        if ($deadline !== null && $deadline->isPast()) {
            return back()->with('error', __('messages.rma_window_passed', [
                'days' => self::RETURN_WINDOW_DAYS,
                'date' => $deadline->format('d.m.Y'),
            ]));
        }

        $data = $request->validate([
            'reason' => 'required|string|max:2000',
            'items' => 'required|array|min:1',
            'items.*.name' => 'required|string',
            'items.*.qty' => 'required|integer|min:1',
            'condition_notes' => 'nullable|string|max:1000',
        ]);

        $rma = $this->rmaService->create($order, $data);

        return back()->with('success', __('messages.rma_created', ['number' => $rma->rma_number]));
    }
}
