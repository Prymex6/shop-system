<?php

namespace App\Listeners;

use App\Events\OrderCreated;
use App\Services\DropshippingService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class HandleDropshippingOnOrder implements ShouldQueue
{
    use InteractsWithQueue;

    public function __construct(protected DropshippingService $dropshippingService) {}

    public function handle(OrderCreated $event): void
    {
        try {
            $order = $event->order;
            $order->load('items.product');

            if ($this->dropshippingService->isDropshipOrder($order)) {
                $this->dropshippingService->processOrder($order);
            }
        } catch (\Exception $e) {
            Log::error('HandleDropshippingOnOrder failed: ' . $e->getMessage());
        }
    }
}
