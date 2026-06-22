<?php

namespace App\Listeners;

use App\Events\OrderCreated;
use App\Mail\Tenant\DownloadLinkMail;
use App\Services\DigitalDeliveryService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class GenerateDigitalDownloads
{
    public function __construct(
        protected DigitalDeliveryService $digitalDelivery
    ) {}

    public function handle(OrderCreated $event): void
    {
        $order = $event->order;

        // Only generate links when payment is confirmed
        if ($order->payment_status !== 'paid') {
            return;
        }

        $hasDigital = $order->items->contains(fn ($item) => $item->product?->type === 'digital');
        if (!$hasDigital) {
            return;
        }

        // Skip if links already exist (e.g. already generated at checkout for COD)
        if ($order->downloadLinks()->exists()) {
            return;
        }

        try {
            $order->load('items.product.digitalFiles');
            $links = $this->digitalDelivery->generateForOrder($order);
            Log::info('Digital download links generated', ['order_number' => $order->order_number]);

            if ($links->isNotEmpty() && $order->customer_email) {
                Mail::to($order->customer_email)->queue(new DownloadLinkMail($order, $links));
            }
        } catch (\Exception $e) {
            Log::error('Failed to generate digital download links', [
                'order_number' => $order->order_number,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
