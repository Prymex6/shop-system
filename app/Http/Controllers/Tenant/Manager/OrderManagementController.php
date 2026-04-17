<?php

namespace App\Http\Controllers\Tenant\Manager;

use App\Events\OrderStatusChanged;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Tenant\Client\OrderTrackingController;
use App\Mail\Tenant\OrderStatusChangedMail;
use App\Models\Tenant\Customer;
use App\Models\Tenant\Order;
use App\Models\Tenant\OrderItem;
use App\Models\Tenant\Product;
use App\Services\InventoryService;
use App\Services\LoyaltyService;
use App\Services\Payment\PaymentGatewayFactory;
use App\Services\RefundService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Inertia\Inertia;

class OrderManagementController extends Controller
{
    public function index(Request $request)
    {
        $query = Order::with(['items', 'customer', 'discountCode'])->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('payment_status')) {
            $query->where('payment_status', $request->payment_status);
        }
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('order_number', 'like', "%{$search}%")
                    ->orWhere('customer_name', 'like', "%{$search}%")
                    ->orWhere('customer_phone', 'like', "%{$search}%");
            });
        }

        $orders = $query->paginate(20)->withQueryString();

        return Inertia::render('Tenant/Manager/Orders/Index', [
            'orders' => $orders,
            'filters' => $request->only(['status', 'type', 'payment_status', 'search']),
        ]);
    }

    public function show(Order $order)
    {
        $order->load(['items', 'shippingMethod']);

        return Inertia::render('Tenant/Manager/Orders/Show', [
            'order' => $order,
        ]);
    }

    /**
     * Which statuses an order may move to from its current status. Without this,
     * a UI bug or replayed request can move a "cancelled" order back to
     * "pending" and then to "cancelled" again — re-triggering stock restoration
     * and loyalty revocation a second time for the same order.
     */
    private const ALLOWED_STATUS_TRANSITIONS = [
        'pending' => ['confirmed', 'paid', 'processing', 'cancelled'],
        'confirmed' => ['paid', 'processing', 'cancelled'],
        'paid' => ['processing', 'shipped', 'cancelled', 'refunded'],
        'processing' => ['shipped', 'delivered', 'cancelled', 'refunded'],
        'shipped' => ['delivered', 'cancelled', 'refunded'],
        'delivered' => ['refunded'],
        'cancelled' => [],
        'refunded' => [],
    ];

    public function updateStatus(Request $request, Order $order)
    {
        $validated = $request->validate([
            'status' => 'required|in:pending,confirmed,paid,processing,shipped,delivered,cancelled,refunded',
        ]);

        $oldStatus = $order->status;
        $newStatus = $validated['status'];

        if ($oldStatus === $newStatus) {
            return back()->with('success', __('messages.order_status_updated'));
        }

        if (!in_array($newStatus, self::ALLOWED_STATUS_TRANSITIONS[$oldStatus] ?? [], true)) {
            return back()->with('error', "Nie można zmienić statusu z \"{$oldStatus}\" na \"{$newStatus}\".");
        }

        $updateData = ['status' => $newStatus];
        // Auto-mark payment as paid for cash-on-delivery when order delivered
        if ($newStatus === 'delivered'
            && $order->payment_method === 'cash_on_delivery'
            && $order->payment_status !== 'paid') {
            $updateData['payment_status'] = 'paid';
        }
        $order->update($updateData);

        Log::info('Order: zmiana statusu', [
            'order_number' => $order->order_number,
            'old_status' => $oldStatus,
            'new_status' => $newStatus,
            'payment_status' => $updateData['payment_status'] ?? $order->payment_status,
            'manager_id' => auth('tenant')->id(),
        ]);

        // Broadcast WebSocket event
        event(new OrderStatusChanged($order, $oldStatus, $newStatus));

        // Send email notification (skip 'pending')
        if ($order->customer_email && $newStatus !== 'pending') {
            try {
                $order->load('items');
                $token = $order->tracking_token ?? OrderTrackingController::generateToken($order->order_number);
                $trackingUrl = route('tenant.order.tracking', $order->order_number) . '?token=' . $token;
                Mail::to($order->customer_email)->queue(new OrderStatusChangedMail($order, $oldStatus, $newStatus, $trackingUrl));
            } catch (\Exception $e) {
                Log::warning('Order status email failed: ' . $e->getMessage());
            }
        }

        // Award loyalty points when order delivered
        if ($newStatus === 'delivered' && $order->customer_id) {
            try {
                $loyaltyService = app(LoyaltyService::class);
                $loyaltyService->awardPointsForOrder($order);

                // Award referral bonus to referrer on customer's first completed order.
                // The update() is the guard: it only affects a row, and only flips the
                // flag, if it's still false — so two concurrent requests transitioning
                // the same order to "delivered" can't both pass a check-then-act race
                // and double-credit the referrer.
                $customer = $order->customer;
                if ($customer && $customer->loyalty_referred_by && !$customer->referral_bonus_awarded) {
                    $claimed = Customer::where('id', $customer->id)
                        ->where('referral_bonus_awarded', false)
                        ->update(['referral_bonus_awarded' => true]);

                    $referrer = $claimed ? Customer::find($customer->loyalty_referred_by) : null;
                    if ($referrer) {
                        // Cheap self-referral guard: same person registering a second
                        // account from the same IP to pay themselves the referral bonus.
                        // Not foolproof (shared IPs, VPNs), but blocks the trivial case
                        // without adding friction for genuine referrals.
                        if ($referrer->registration_ip && $referrer->registration_ip === $customer->registration_ip) {
                            Log::warning('Referral bonus skipped: referrer and referred customer share a registration IP', [
                                'referrer_id' => $referrer->id,
                                'customer_id' => $customer->id,
                                'ip' => $customer->registration_ip,
                            ]);
                        } else {
                            $loyaltyService->awardReferralBonus($referrer);
                        }
                    }
                }
            } catch (\Exception $e) {
                Log::warning('Loyalty points award failed: ' . $e->getMessage());
            }
        }

        // When cancelled: attempt payment refund if order was paid
        if ($newStatus === 'cancelled' && $oldStatus !== 'cancelled' && $order->payment_status === 'paid') {
            // Refundable amount accounts for any RMA/partial refund already
            // issued on this order — without this check, cancelling an order
            // that already had a partial refund would issue a second, FULL
            // gateway refund on top of it, paying out more than the order
            // was ever worth.
            $refundable = max(0, (float) $order->total - app(RefundService::class)->alreadyRefunded($order));

            if ($refundable <= 0) {
                $order->update(['payment_status' => 'refunded']);
            } elseif (in_array($order->payment_method, ['przelewy24', 'payu', 'tpay', 'stripe', 'online'])) {
                try {
                    $gateway = PaymentGatewayFactory::make($order->payment_method);
                    $result = $gateway->refund($order, $refundable);
                    if ($result['success'] ?? false) {
                        $order->update(['payment_status' => 'refunded']);
                        Log::info('Order: zwrot płatności pomyślny', ['order_number' => $order->order_number, 'method' => $order->payment_method]);
                    } else {
                        $order->update(['payment_status' => 'refund_failed']);
                        Log::warning('Refund failed for order ' . $order->order_number . ': ' . ($result['error'] ?? ''));
                    }
                } catch (\Exception $e) {
                    $order->update(['payment_status' => 'refund_failed']);
                    Log::error('Refund exception for order ' . $order->order_number . ': ' . $e->getMessage());
                }
            } else {
                // Offline payment method (cash on delivery / bank transfer) —
                // no gateway ever took the money, so there's nothing to call.
                // Cancelling here is the manager's acknowledgement that
                // they'll return it manually outside the system.
                $order->update(['payment_status' => 'refunded']);
            }
        }

        // When cancelled: restore stock and revoke loyalty points
        if ($newStatus === 'cancelled' && $oldStatus !== 'cancelled') {
            try {
                $order->load('items');
                app(InventoryService::class)->releaseForOrder($order);
                Log::info('Order: przywrócono stan magazynowy', ['order_number' => $order->order_number]);
            } catch (\Exception $e) {
                Log::warning('Stock restore failed: ' . $e->getMessage());
            }

            if ($order->customer_id) {
                try {
                    app(LoyaltyService::class)->revokePointsForOrder($order);
                } catch (\Exception $e) {
                    Log::warning('Loyalty revoke failed: ' . $e->getMessage());
                }
            }
        }

        return back()->with('success', __('messages.order_status_updated'));
    }

    public function updatePaymentStatus(Request $request, Order $order)
    {
        $validated = $request->validate([
            'payment_status' => 'required|in:pending,paid,failed,refunded,refund_failed',
        ]);

        $old = $order->payment_status;
        $order->update(['payment_status' => $validated['payment_status']]);

        Log::info('Order: zmiana statusu płatności', [
            'order_number' => $order->order_number,
            'old_payment_status' => $old,
            'new_payment_status' => $validated['payment_status'],
            'manager_id' => auth('tenant')->id(),
        ]);

        return back()->with('success', __('messages.payment_status_updated'));
    }

    // ──────────────────────────────────────────────────────────────────────────
    // MANUAL ORDER — product list for modal
    // ──────────────────────────────────────────────────────────────────────────

    public function manualProducts(Request $request)
    {
        $q = trim($request->get('q', ''));

        $query = Product::published()
            ->with(['images' => fn ($i) => $i->orderBy('sort_order')->limit(1), 'variants'])
            ->select(['id', 'name', 'slug', 'price', 'compare_price', 'sku', 'stock_quantity', 'type', 'category_id']);

        if ($q) {
            $query->where(fn ($b) => $b->where('name', 'like', "%{$q}%")->orWhere('sku', 'like', "%{$q}%"));
        }

        $products = $query->orderBy('name')->limit(80)->get()->map(fn ($p) => [
            'id' => $p->id,
            'name' => $p->name,
            'sku' => $p->sku,
            'price' => (float) $p->price,
            'compare_price' => $p->compare_price ? (float) $p->compare_price : null,
            'stock' => $p->stock_quantity,
            'product_type' => $p->type,
            'image' => $p->images->first()?->url,
            'variants' => $p->variants->map(fn ($v) => [
                'id' => $v->id,
                'label' => $v->label(),
                'price' => (float) ($v->price ?? $p->price),
                'stock' => $v->stock_quantity,
                'sku' => $v->sku ?? null,
            ]),
        ]);

        return response()->json($products);
    }

    // ──────────────────────────────────────────────────────────────────────────
    // MANUAL ORDER — create order from manager panel
    // ──────────────────────────────────────────────────────────────────────────

    public function manualStore(Request $request)
    {
        $validated = $request->validate([
            'customer_name' => ['required', 'string', 'max:200'],
            'customer_email' => ['nullable', 'email', 'max:200'],
            'customer_phone' => ['nullable', 'string', 'max:30'],
            'shipping_first_name' => ['nullable', 'string', 'max:100'],
            'shipping_last_name' => ['nullable', 'string', 'max:100'],
            'shipping_street' => ['nullable', 'string', 'max:200'],
            'shipping_city' => ['nullable', 'string', 'max:100'],
            'shipping_postal_code' => ['nullable', 'string', 'max:20'],
            'shipping_country' => ['nullable', 'string', 'max:5'],
            'payment_method' => ['required', 'string', 'in:cash,transfer,cod,card,other'],
            'payment_status' => ['required', 'string', 'in:pending,paid'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'internal_notes' => ['nullable', 'string', 'max:1000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer', 'exists:products,id'],
            'items.*.variant_id' => ['nullable', 'integer'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.price' => ['required', 'numeric', 'min:0'],
        ]);

        DB::beginTransaction();
        try {
            $shippingAddress = array_filter([
                'first_name' => $validated['shipping_first_name'] ?? '',
                'last_name' => $validated['shipping_last_name'] ?? '',
                'address1' => $validated['shipping_street'] ?? '',
                'city' => $validated['shipping_city'] ?? '',
                'postal_code' => $validated['shipping_postal_code'] ?? '',
                'country' => $validated['shipping_country'] ?? 'PL',
            ]);

            $subtotal = collect($validated['items'])->sum(fn ($i) => $i['price'] * $i['quantity']);

            $order = Order::create([
                'customer_name' => $validated['customer_name'],
                'customer_email' => $validated['customer_email'] ?? null,
                'customer_phone' => $validated['customer_phone'] ?? null,
                'shipping_address' => $shippingAddress ?: null,
                'subtotal' => $subtotal,
                'shipping_cost' => 0,
                'discount' => 0,
                'tax' => 0,
                'total' => $subtotal,
                'currency' => 'PLN',
                'payment_method' => $validated['payment_method'],
                'payment_status' => $validated['payment_status'],
                'paid_at' => $validated['payment_status'] === 'paid' ? now() : null,
                'status' => 'confirmed',
                'fulfillment_status' => 'unfulfilled',
                'notes' => $validated['notes'] ?? null,
                'internal_notes' => ($validated['internal_notes'] ?? null)
                    ? '[RĘCZNE] ' . $validated['internal_notes']
                    : '[RĘCZNE — dodane przez managera]',
            ]);

            foreach ($validated['items'] as $item) {
                $product = Product::lockForUpdate()->findOrFail($item['product_id']);
                $variant = $item['variant_id'] ? $product->variants->find($item['variant_id']) : null;

                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $product->id,
                    'variant_id' => $variant?->id,
                    'name' => $product->name,
                    'sku' => $variant?->sku ?? $product->sku,
                    'variant_label' => $variant?->label(),
                    'product_type' => $product->type ?? 'physical',
                    'price' => $item['price'],
                    'tax_rate' => 0,
                    'quantity' => $item['quantity'],
                    'total' => $item['price'] * $item['quantity'],
                ]);

                // Manual (phone / in-person) sales must reduce stock the same
                // way an online checkout does — otherwise inventory silently
                // overstates what's actually available and online orders can
                // oversell units that were already handed over in person.
                if ($product->type !== 'digital') {
                    if ($variant) {
                        $variant->decrement('stock_quantity', $item['quantity']);
                    } elseif ($product->track_stock) {
                        $product->decrement('stock_quantity', $item['quantity']);
                    }
                }
            }

            DB::commit();

            Log::info('Manual order created', [
                'order_number' => $order->order_number,
                'manager_id' => auth('tenant')->id(),
                'total' => $order->total,
            ]);

            return response()->json([
                'success' => true,
                'order_number' => $order->order_number,
                'order_id' => $order->id,
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Manual order error', ['error' => $e->getMessage()]);

            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
}
