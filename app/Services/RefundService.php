<?php

namespace App\Services;

use App\Events\RefundRequested;
use App\Mail\Tenant\RefundProcessedMail;
use App\Models\Tenant\Order;
use App\Models\Tenant\Refund;
use App\Services\Payment\PaymentGatewayFactory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class RefundService
{
    /**
     * No online gateway can be charged back for these — the customer never
     * paid through one. Refunding them means the manager physically returns
     * the money outside the system; process() records that acknowledgement
     * instead of calling a gateway that doesn't exist for this order.
     */
    private const OFFLINE_PAYMENT_METHODS = ['cash_on_delivery', 'bank_transfer'];

    public function __construct(
        private PaymentGatewayFactory $gatewayFactory,
        private InventoryService $inventoryService,
        private LoyaltyService $loyaltyService
    ) {}

    /**
     * Total already refunded (completed) for this order, so a new refund
     * request can never push the order's total refunded amount past what
     * was actually paid.
     */
    public function alreadyRefunded(Order $order): float
    {
        return (float) Refund::where('order_id', $order->id)
            ->where('status', 'completed')
            ->sum('amount');
    }

    /**
     * Create a new refund request (by customer or manager).
     */
    public function request(Order $order, array $data): Refund
    {
        $requested = (float) ($data['amount'] ?? $order->total);
        $refundable = max(0, (float) $order->total - $this->alreadyRefunded($order));
        $amount = min($requested, $refundable);

        $refund = Refund::create([
            'order_id' => $order->id,
            'amount' => $amount,
            'reason' => $data['reason'] ?? null,
            'notes' => $data['notes'] ?? null,
            'items' => $data['items'] ?? null,
            'images' => $data['images'] ?? null,
            'status' => $data['status'] ?? 'pending',
        ]);

        RefundRequested::dispatch($refund);

        return $refund;
    }

    /**
     * Approve a pending refund (manager action, before payment processing).
     */
    public function approve(Refund $refund): void
    {
        $refund->update(['status' => 'approved']);
    }

    /**
     * Reject a refund request.
     */
    public function reject(Refund $refund, ?string $reason = null): void
    {
        $refund->update([
            'status' => 'rejected',
            'notes' => $reason ?? $refund->notes,
        ]);
    }

    /**
     * Process (execute) an approved refund through the payment gateway.
     *
     * @throws \Exception
     */
    public function process(Refund $refund): Refund
    {
        if (!$refund->isProcessable()) {
            throw new \Exception('Zwrot nie może być przetworzony w obecnym statusie: ' . $refund->status);
        }

        // Lock the order row for the duration of the transaction — without this,
        // two refunds on the same order processed concurrently could both read
        // the same alreadyRefunded() total, both pass the overpay check, and
        // together refund more than the order was ever paid.
        $order = DB::transaction(function () use ($refund) {
            $order = Order::whereKey($refund->order_id)->lockForUpdate()->first();

            // Re-check status inside the lock in case another request just
            // processed or rejected this exact refund a moment ago.
            $refund->refresh();
            if (!$refund->isProcessable()) {
                throw new \Exception('Zwrot nie może być przetworzony w obecnym statusie: ' . $refund->status);
            }

            $refundable = max(0, (float) $order->total - $this->alreadyRefunded($order));
            if ((float) $refund->amount > $refundable) {
                throw new \Exception('Kwota zwrotu przekracza pozostałą do zwrotu wartość zamówienia.');
            }

            $isOffline = in_array($order->payment_method, self::OFFLINE_PAYMENT_METHODS, true);

            if ($isOffline) {
                // No gateway was ever charged for cash-on-delivery/bank-transfer
                // orders — a manager clicking "process" here IS the record that
                // they returned the money manually outside the system.
                $refundId = null;
            } else {
                $gateway = $this->gatewayFactory->make($order->payment_method);
                $result = $gateway->refund($order, (float) $refund->amount);

                if (!($result['success'] ?? false)) {
                    // Previously this fell through and marked the refund
                    // 'completed' regardless of gateway success — silently
                    // recording money as returned that never moved, and
                    // permanently blocking a real retry since alreadyRefunded()
                    // would already count it.
                    throw new \Exception('Zwrot przez bramkę płatności nie powiódł się: ' . ($result['error'] ?? 'nieznany błąd'));
                }

                $refundId = $result['refund_id'] ?? null;
            }

            $refund->update([
                'status' => 'completed',
                'gateway_refund_id' => $refundId,
                'processed_at' => now(),
            ]);

            $totalRefunded = $this->alreadyRefunded($order);
            $isFullRefund = $totalRefunded >= (float) $order->total;

            $order->update([
                'payment_status' => $isFullRefund ? 'refunded' : 'partially_refunded',
                'status' => $isFullRefund ? 'refunded' : $order->status,
            ]);

            return $order;
        });

        $isFullRefund = $this->alreadyRefunded($order) >= (float) $order->total;

        // Restore stock only for the items this specific refund covers — restocking
        // the whole order on a partial refund would create phantom inventory.
        if (!empty($refund->items)) {
            foreach ($refund->items as $item) {
                $name = $item['name'] ?? null;
                $qty = (int) ($item['qty'] ?? 0);
                if ($name && $qty > 0) {
                    $this->inventoryService->releaseOrderItemQuantity($order, $name, $qty);
                }
            }
        } else {
            $this->inventoryService->releaseForOrder($order);
        }

        // Refunded orders shouldn't keep loyalty points earned from that spend,
        // and this closes the "redeem points, get a full refund, keep both" gap.
        if ($isFullRefund) {
            $this->loyaltyService->revokePointsForOrder($order);
        }

        // Notify customer — the refund itself already succeeded and is committed
        // above, so a mail failure here must never look like the refund failed.
        if ($order->customer_email) {
            try {
                Mail::to($order->customer_email)->queue(new RefundProcessedMail($refund));
            } catch (\Exception $e) {
                Log::warning('Refund processed but confirmation email failed: ' . $e->getMessage());
            }
        }

        AuditService::log('refund.processed', $refund, [], [
            'order_id' => $order->id,
            'amount' => (string) $refund->amount,
            'full' => $isFullRefund,
        ]);

        return $refund->refresh();
    }
}
