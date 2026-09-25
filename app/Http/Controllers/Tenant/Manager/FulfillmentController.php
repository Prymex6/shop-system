<?php

namespace App\Http\Controllers\Tenant\Manager;

use App\Http\Controllers\Controller;
use App\Mail\Tenant\DownloadLinkMail;
use App\Models\Tenant\Order;
use App\Services\DigitalDeliveryService;
use App\Services\FulfillmentService;
use App\Services\Shipping\InPostGateway;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use RuntimeException;

class FulfillmentController extends Controller
{
    public function __construct(
        private DigitalDeliveryService $digitalDelivery,
        private FulfillmentService $fulfillment,
        private InPostGateway $inPost,
    ) {}

    public function update(Request $request, Order $order)
    {
        $data = $request->validate([
            'fulfillment_status' => 'required|in:unfulfilled,processing,shipped,delivered,cancelled',
            'item_ids' => 'nullable|array',
            'item_ids.*' => 'integer|exists:order_items,id',
        ]);

        $this->fulfillment->updateStatus($order, $data['fulfillment_status'], $data['item_ids'] ?? null);

        return back()->with('success', __('messages.fulfillment_status_updated'));
    }

    public function addTracking(Request $request, Order $order)
    {
        $data = $request->validate([
            'tracking_number' => 'required|string|max:100',
            'tracking_carrier' => 'nullable|string|max:100',
        ]);

        $order->update($data + ['fulfillment_status' => 'shipped', 'shipped_at' => now()]);

        return back()->with('success', __('messages.tracking_added'));
    }

    /**
     * Buy an InPost label for an order the customer picked a locker for.
     *
     * Ordering a second label for the same order would charge the shop twice
     * and leave two parcels expected at the locker, so an order that already
     * has a tracking number is refused rather than re-sent.
     */
    public function createLabel(Order $order)
    {
        if (filled($order->tracking_number)) {
            return back()->with('error', __('messages.order_already_has_tracking'));
        }

        if (!$this->inPost->isConfigured()) {
            return back()->with('error', __('messages.inpost_not_configured'));
        }

        try {
            $shipment = $this->inPost->createShipment($order);
        } catch (RuntimeException $error) {
            return back()->with('error', $error->getMessage());
        }

        $order->update([
            'tracking_number' => $shipment['trackingNumber'],
            'tracking_carrier' => 'inpost',
            'shipped_at' => now(),
            'fulfillment_status' => 'shipped',
        ]);

        return back()->with('success', __('messages.inpost_label_created'));
    }

    public function regenerateDownloads(Order $order)
    {
        if (!$order->hasDigitalItems()) {
            return back()->with('error', __('messages.order_has_no_digital_items'));
        }

        $links = $this->digitalDelivery->regenerateForOrder($order);

        if ($links->isNotEmpty() && $order->customer_email) {
            Mail::to($order->customer_email)->queue(new DownloadLinkMail($order, $links));
        }

        return back()->with('success', __('messages.download_links_regenerated'));
    }
}
