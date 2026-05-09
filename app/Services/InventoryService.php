<?php

namespace App\Services;

use App\Models\Tenant\Order;
use App\Models\Tenant\Product;
use App\Models\Tenant\ProductVariant;
use App\Models\Tenant\StockMovement;

class InventoryService
{
    /**
     * Decrement stock when order is placed (or paid).
     */
    public function reserveForOrder(Order $order): void
    {
        foreach ($order->items as $item) {
            if ($item->isDigital()) {
                continue;
            }

            if ($item->variant_id) {
                $variant = ProductVariant::find($item->variant_id);
                if ($variant) {
                    $this->adjustVariant($variant, -$item->quantity, 'sale', 'Order #' . $order->order_number, $order->id, 'order');
                }
            } elseif ($item->product_id) {
                $product = Product::find($item->product_id);
                if ($product && $product->track_stock) {
                    $this->adjustProduct($product, -$item->quantity, 'sale', 'Order #' . $order->order_number, $order->id, 'order');
                }
            }
        }
    }

    /**
     * Restore stock when order is cancelled or refunded.
     */
    public function releaseForOrder(Order $order): void
    {
        foreach ($order->items as $item) {
            if ($item->isDigital()) {
                continue;
            }

            if ($item->variant_id) {
                $variant = ProductVariant::find($item->variant_id);
                if ($variant) {
                    $this->adjustVariant($variant, $item->quantity, 'refund', 'Cancellation Order #' . $order->order_number, $order->id, 'order');
                }
            } elseif ($item->product_id) {
                $product = Product::find($item->product_id);
                if ($product && $product->track_stock) {
                    $this->adjustProduct($product, $item->quantity, 'refund', 'Cancellation Order #' . $order->order_number, $order->id, 'order');
                }
            }
        }
    }

    /**
     * Restore stock for a single returned line item (RMA), matched by the order
     * it belongs to and the item's display name at time of purchase. Used instead
     * of releaseForOrder() when only part of an order is being returned — restocking
     * the whole order for a partial return would create phantom inventory.
     */
    public function releaseOrderItemQuantity(Order $order, string $itemName, int $qty): void
    {
        $item = $order->items()->where('name', $itemName)->first();

        if (!$item || $item->isDigital() || $qty <= 0) {
            return;
        }

        $qty = min($qty, $item->quantity);

        if ($item->variant_id) {
            $variant = ProductVariant::find($item->variant_id);
            if ($variant) {
                $this->adjustVariant($variant, $qty, 'refund', "Zwrot RMA: {$itemName}", $order->id, 'rma');
            }
        } elseif ($item->product_id) {
            $product = Product::find($item->product_id);
            if ($product && $product->track_stock) {
                $this->adjustProduct($product, $qty, 'refund', "Zwrot RMA: {$itemName}", $order->id, 'rma');
            }
        }
    }

    /**
     * Manual stock adjustment by manager.
     */
    public function adjustProduct(Product $product, int $quantityChange, string $type, ?string $reason = null, ?int $referenceId = null, ?string $referenceType = null): void
    {
        $product->increment('stock_quantity', $quantityChange);
        $product->refresh();

        StockMovement::create([
            'product_id' => $product->id,
            'quantity_change' => $quantityChange,
            'quantity_after' => $product->stock_quantity,
            'type' => $type,
            'reason' => $reason,
            'reference_id' => $referenceId,
            'reference_type' => $referenceType,
        ]);
    }

    public function adjustVariant(ProductVariant $variant, int $quantityChange, string $type, ?string $reason = null, ?int $referenceId = null, ?string $referenceType = null): void
    {
        $variant->increment('stock_quantity', $quantityChange);
        $variant->refresh();

        StockMovement::create([
            'product_id' => $variant->product_id,
            'variant_id' => $variant->id,
            'quantity_change' => $quantityChange,
            'quantity_after' => $variant->stock_quantity,
            'type' => $type,
            'reason' => $reason,
            'reference_id' => $referenceId,
            'reference_type' => $referenceType,
        ]);
    }

    /**
     * Check if stock is sufficient for placing an order.
     */
    public function checkAvailability(array $items): array
    {
        $errors = [];

        foreach ($items as $item) {
            $productId = $item['product_id'] ?? null;
            $variantId = $item['variant_id'] ?? null;
            $quantity = $item['quantity'] ?? 1;

            if ($variantId) {
                $variant = ProductVariant::find($variantId);
                if ($variant && $variant->stock_quantity < $quantity) {
                    $errors[] = "Niewystarczający stan magazynowy dla produktu: {$variant->product->name} ({$variant->label()})";
                }
            } elseif ($productId) {
                $product = Product::find($productId);
                if ($product && $product->track_stock && !$product->allow_backorder && $product->stock_quantity < $quantity) {
                    $errors[] = "Niewystarczający stan magazynowy dla produktu: {$product->name}";
                }
            }
        }

        return $errors;
    }
}
