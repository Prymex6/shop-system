<?php

namespace App\Services;

use App\Models\Tenant\Order;
use App\Services\Payment\PaymentGatewayFactory;
use Illuminate\Support\Facades\Log;

/**
 * Cancels an order with the full set of side effects a cancellation
 * requires: a real gateway refund (not just flipping payment_status to
 * 'refunded' in the DB), stock restoration, and loyalty-point revocation.
 * Mirrors the cancellation branch of
 * OrderManagementController::updateStatus(), factored out so any other
 * cancellation entry point (e.g. the fraud-review "block" action) gets the
 * same guarantees instead of writing the status directly.
 */
class OrderCancellationService
{
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

    public function __construct(
        private LoyaltyService $loyaltyService,
        private RefundService $refundService,
        private InventoryService $inventoryService,
    ) {}

    public function canCancel(Order $order): bool
    {
        return in_array('cancelled', self::ALLOWED_STATUS_TRANSITIONS[$order->status] ?? [], true);
    }

    public function cancel(Order $order): array
    {
        if (!$this->canCancel($order)) {
            return ['success' => false, 'message' => __('messages.order_cannot_cancel_from_status', ['status' => $order->status])];
        }

        if ($order->payment_status === 'paid') {
            // Refundable amount accounts for any RMA/partial refund already
            // issued on this order — without this check, cancelling an order
            // that already had a partial refund would issue a second, FULL
            // gateway refund on top of it, paying out more than the order
            // was ever worth.
            $refundable = max(0, (float) $order->total - $this->refundService->alreadyRefunded($order));

            if ($refundable <= 0) {
                $order->payment_status = 'refunded';
            } elseif (in_array($order->payment_method, ['cash_on_delivery', 'bank_transfer'], true)) {
                // Offline payment method — no gateway ever took the money, so
                // there's nothing to call. Cancelling here is the manager's
                // acknowledgement that they'll return it manually outside the system.
                $order->payment_status = 'refunded';
            } else {
                try {
                    $gateway = PaymentGatewayFactory::make($order->payment_method);
                    $result = $gateway->refund($order, $refundable);
                    $order->payment_status = ($result['success'] ?? false) ? 'refunded' : 'refund_failed';
                    if (!($result['success'] ?? false)) {
                        Log::warning('Refund failed for order ' . $order->order_number . ': ' . ($result['error'] ?? ''));
                    }
                } catch (\Exception $e) {
                    $order->payment_status = 'refund_failed';
                    Log::error('Refund exception for order ' . $order->order_number . ': ' . $e->getMessage());
                }
            }
        }

        $order->status = 'cancelled';
        $order->save();

        try {
            $order->load('items');
            $this->inventoryService->releaseForOrder($order);
        } catch (\Exception $e) {
            Log::warning('Stock restore failed: ' . $e->getMessage());
        }

        if ($order->customer_id) {
            try {
                $this->loyaltyService->revokePointsForOrder($order);
            } catch (\Exception $e) {
                Log::warning('Loyalty revoke failed: ' . $e->getMessage());
            }
        }

        return ['success' => true, 'message' => __('messages.order_cancelled')];
    }
}
