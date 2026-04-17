<?php

namespace App\Services;

use App\Mail\Tenant\OrderShippedMail;
use App\Models\Tenant\Order;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Order.fulfillment_status used to be the only place fulfillment state
 * lived — a single switch for the whole order, so "shipped 2 of 3 items"
 * couldn't be expressed at all: the manager either had to click the whole
 * order "shipped" early (lying about the item still missing) or leave
 * everything at "processing" (hiding that most of the order already went
 * out). order_items.fulfillment_status now carries the real per-item
 * state; the order's own fulfillment_status is derived from its items
 * here whenever it has any, so it can't silently misrepresent what's
 * actually been packed.
 */
class FulfillmentService
{
    public function updateStatus(Order $order, string $status, ?array $itemIds = null): Order
    {
        if ($order->items()->doesntExist()) {
            // Nothing to derive a per-item status from (e.g. an order
            // created without line items) — apply the status directly
            // rather than leaving fulfillment_status untouched.
            return $this->applyStatus($order, $status);
        }

        $items = $itemIds
            ? $order->items()->whereIn('id', $itemIds)
            : $order->items();

        $items->update(['fulfillment_status' => $status]);

        $order->refresh()->load('items');

        return $this->applyStatus($order, $this->aggregate($this->relevantStatuses($order)));
    }

    private function applyStatus(Order $order, string $status): Order
    {
        $wasShippedOrDelivered = in_array($order->fulfillment_status, ['shipped', 'delivered'], true);

        $order->fulfillment_status = $status;

        if ($status === 'shipped' && !$order->shipped_at) {
            $order->shipped_at = now();
        }

        if ($status === 'delivered' && !$order->delivered_at) {
            $order->delivered_at = now();
        }

        $order->save();

        // Only fire the "your order shipped" email the moment the order
        // first reaches a fully-shipped status — not on every item-level
        // update, so a partial shipment doesn't tell the customer their
        // whole order is on the way.
        if ($status === 'shipped' && !$wasShippedOrDelivered && $order->customer_email) {
            try {
                Mail::to($order->customer_email)->queue(new OrderShippedMail($order));
            } catch (\Exception $e) {
                Log::warning('Order shipped but notification email failed: ' . $e->getMessage());
            }
        }

        return $order;
    }

    /**
     * Digital items are delivered instantly via download links at checkout,
     * not through this shipping workflow — if they were left counted, a
     * mixed cart's digital line (which nobody ever marks manually) would
     * permanently stall the order at "processing" even once every physical
     * item shipped and arrived.
     */
    private function relevantStatuses(Order $order): Collection
    {
        $physical = $order->items->where('product_type', 'physical');

        return $physical->isNotEmpty()
            ? $physical->pluck('fulfillment_status')
            : $order->items->pluck('fulfillment_status');
    }

    private function aggregate(Collection $statuses): string
    {
        if ($statuses->unique()->count() === 1) {
            return $statuses->first();
        }

        $active = $statuses->reject(fn ($s) => $s === 'cancelled');

        if ($active->isEmpty()) {
            return 'cancelled';
        }

        if ($active->contains('unfulfilled')) {
            return $active->every(fn ($s) => $s === 'unfulfilled') ? 'unfulfilled' : 'processing';
        }

        if ($active->every(fn ($s) => $s === 'delivered')) {
            return 'delivered';
        }

        if ($active->every(fn ($s) => in_array($s, ['shipped', 'delivered'], true))) {
            return 'shipped';
        }

        return 'processing';
    }
}
