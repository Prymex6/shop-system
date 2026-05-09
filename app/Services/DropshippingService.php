<?php

namespace App\Services;

use App\Mail\Tenant\DropshipOrderMail;
use App\Models\Tenant\Order;
use App\Models\Tenant\PurchaseOrder;
use App\Models\Tenant\PurchaseOrderItem;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class DropshippingService
{
    public function isDropshipOrder(Order $order): bool
    {
        foreach ($order->items as $item) {
            if ($item->product && $item->product->is_dropship) {
                return true;
            }
        }

        return false;
    }

    public function processOrder(Order $order): void
    {
        $order->load('items.product');

        // Group by supplier
        $bySupplier = [];
        foreach ($order->items as $item) {
            if (!$item->product || !$item->product->is_dropship) {
                continue;
            }

            $supplierId = $item->product->dropship_supplier_id;
            if (!$supplierId) {
                continue;
            }

            $bySupplier[$supplierId][] = $item;
        }

        foreach ($bySupplier as $supplierId => $items) {
            try {
                $po = PurchaseOrder::create([
                    'supplier_id' => $supplierId,
                    'status' => 'sent',
                    'notes' => "Dropship for Order #{$order->order_number}",
                    'total' => collect($items)->sum(fn ($i) => $i->price * $i->quantity),
                    'ordered_at' => now(),
                ]);

                foreach ($items as $item) {
                    PurchaseOrderItem::create([
                        'purchase_order_id' => $po->id,
                        'product_id' => $item->product_id,
                        'variant_id' => $item->variant_id,
                        'quantity' => $item->quantity,
                        'unit_cost' => $item->product->cost_price ?? $item->price,
                        'total_cost' => ($item->product->cost_price ?? $item->price) * $item->quantity,
                    ]);
                }

                $supplier = $po->supplier;
                if ($supplier && $supplier->email) {
                    Mail::to($supplier->email)->send(new DropshipOrderMail($order, $po, $supplier));
                }
            } catch (\Exception $e) {
                Log::error("Dropshipping processOrder error for supplier {$supplierId}: " . $e->getMessage());
            }
        }
    }
}
