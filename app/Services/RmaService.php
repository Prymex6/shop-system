<?php

namespace App\Services;

use App\Mail\Tenant\RmaCreatedMail;
use App\Models\Tenant\Order;
use App\Models\Tenant\RmaRequest;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class RmaService
{
    public function __construct(protected InventoryService $inventoryService) {}

    public function create(Order $order, array $data): RmaRequest
    {
        $rma = new RmaRequest;

        $rma->fill([
            'order_id' => $order->id,
            'rma_number' => $rma->generateRmaNumber(),
            'customer_email' => $order->customer_email,
            'customer_name' => $order->customer_name,
            'status' => 'pending',
            'reason' => $data['reason'],
            'items' => $data['items'],
            'condition_notes' => $data['condition_notes'] ?? null,
        ]);

        $rma->save();

        // Send email notification to customer
        try {
            Mail::to($order->customer_email)->send(new RmaCreatedMail($rma, $order));
        } catch (\Throwable $e) {
            // Log but don't throw — RMA is created
            Log::warning('RMA mail failed: ' . $e->getMessage());
        }

        return $rma;
    }

    public function approve(RmaRequest $rma): void
    {
        $rma->update([
            'status' => 'approved',
            'approved_at' => now(),
        ]);
    }

    public function reject(RmaRequest $rma, string $reason): void
    {
        $rma->update([
            'status' => 'rejected',
            'condition_notes' => $reason,
        ]);
    }

    public function markReceived(RmaRequest $rma): void
    {
        $rma->update([
            'status' => 'received',
            'received_at' => now(),
        ]);

        // Physical goods are back in the warehouse — restock the returned line items.
        foreach ((array) $rma->items as $item) {
            $name = $item['name'] ?? null;
            $qty = (int) ($item['qty'] ?? 0);

            if ($name && $qty > 0) {
                $this->inventoryService->releaseOrderItemQuantity($rma->order, $name, $qty);
            }
        }
    }
}
