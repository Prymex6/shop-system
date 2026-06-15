<?php

namespace App\Listeners;

use App\Events\OrderCreated;
use App\Services\Printer\ThermalPrinterService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class PrintOrderReceipt implements ShouldQueue
{
    use InteractsWithQueue;

    protected ThermalPrinterService $printerService;

    /**
     * Create the event listener.
     */
    public function __construct(ThermalPrinterService $printerService)
    {
        $this->printerService = $printerService;
    }

    /**
     * Handle the event.
     */
    public function handle(OrderCreated $event): void
    {
        try {
            // Only print if order is paid
            if ($event->order->payment_status !== 'paid') {
                Log::info('Order not paid yet, skipping print', [
                    'order_id' => $event->order->id,
                    'payment_status' => $event->order->payment_status,
                ]);

                return;
            }

            // Print the receipt
            $printed = $this->printerService->printOrderReceipt($event->order);

            if ($printed) {
                Log::info('Order receipt printed successfully', [
                    'order_id' => $event->order->id,
                    'order_number' => $event->order->order_number,
                ]);
            } else {
                Log::warning('Order receipt printing failed or disabled', [
                    'order_id' => $event->order->id,
                    'order_number' => $event->order->order_number,
                ]);
            }

        } catch (\Exception $e) {
            Log::error('Error printing order receipt', [
                'order_id' => $event->order->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Don't fail the whole order creation if printing fails
            // Just log the error and continue
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(OrderCreated $event, \Throwable $exception): void
    {
        Log::error('Print job failed completely', [
            'order_id' => $event->order->id,
            'error' => $exception->getMessage(),
        ]);
    }
}
