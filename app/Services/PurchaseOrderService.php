<?php

namespace App\Services;

use App\Models\Tenant\Product;
use App\Models\Tenant\ProductVariant;
use App\Models\Tenant\PurchaseOrder;
use App\Models\Tenant\PurchaseOrderItem;
use App\Models\Tenant\Supplier;
use Illuminate\Support\Facades\DB;

class PurchaseOrderService
{
    public function create(Supplier $supplier, array $data): PurchaseOrder
    {
        return DB::transaction(function () use ($supplier, $data) {
            $po = PurchaseOrder::create([
                'supplier_id' => $supplier->id,
                'status' => $data['status'] ?? 'draft',
                'notes' => $data['notes'] ?? null,
                'expected_at' => $data['expected_at'] ?? null,
            ]);

            $totalCost = 0;

            foreach ($data['items'] as $item) {
                PurchaseOrderItem::create([
                    'purchase_order_id' => $po->id,
                    'product_id' => $item['product_id'] ?? null,
                    'variant_id' => $item['variant_id'] ?? null,
                    'quantity' => $item['quantity'],
                    'unit_cost' => $item['unit_cost'],
                    'received_quantity' => 0,
                ]);

                $totalCost += $item['quantity'] * $item['unit_cost'];
            }

            $po->update(['total_cost' => $totalCost]);

            return $po->fresh(['items']);
        });
    }

    public function receive(PurchaseOrder $po, array $items): void
    {
        DB::transaction(function () use ($po, $items) {
            $allReceived = true;
            $anyReceived = false;

            foreach ($items as $itemData) {
                // Locked so a retried/duplicated submit (network retry, double-click)
                // can't re-add the same quantity on top of itself, and capped so an
                // item can never be received past what was actually ordered.
                $poItem = PurchaseOrderItem::where('purchase_order_id', $po->id)
                    ->where('id', $itemData['id'])
                    ->lockForUpdate()
                    ->firstOrFail();

                $remaining = max(0, $poItem->quantity - $poItem->received_quantity);
                $receivedQty = min((int) $itemData['received_quantity'], $remaining);

                if ($receivedQty <= 0) {
                    if ($poItem->received_quantity < $poItem->quantity) {
                        $allReceived = false;
                    }

                    continue;
                }

                $poItem->update(['received_quantity' => $poItem->received_quantity + $receivedQty]);

                if ($receivedQty > 0) {
                    $anyReceived = true;

                    // Update product stock
                    if ($poItem->product_id) {
                        if ($poItem->variant_id) {
                            ProductVariant::where('id', $poItem->variant_id)
                                ->increment('stock_quantity', $receivedQty);
                        } else {
                            Product::where('id', $poItem->product_id)
                                ->increment('stock_quantity', $receivedQty);
                        }
                    }
                }

                if ($poItem->received_quantity < $poItem->quantity) {
                    $allReceived = false;
                }
            }

            $newStatus = $allReceived ? 'received' : ($anyReceived ? 'partial' : $po->status);
            if ($anyReceived) {
                $po->update(['status' => $newStatus, 'received_at' => now()]);
            }
        });
    }
}
