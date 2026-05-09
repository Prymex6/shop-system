<?php

namespace App\Services;

use App\Models\Tenant\Order;
use App\Models\Tenant\Product;
use App\Models\Tenant\ProductVariant;
use App\Models\Tenant\ProductWarehouseStock;
use App\Models\Tenant\Warehouse;
use App\Models\Tenant\WarehouseTransfer;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class MultiWarehouseService
{
    public function getStock(Product $product, ?ProductVariant $variant = null): Collection
    {
        return ProductWarehouseStock::with('warehouse')
            ->where('product_id', $product->id)
            ->where('variant_id', $variant?->id)
            ->get();
    }

    public function adjustStock(int $warehouseId, Product $product, int $qty, string $reason): void
    {
        DB::transaction(function () use ($warehouseId, $product, $qty) {
            $stock = ProductWarehouseStock::lockForUpdate()->firstOrCreate(
                [
                    'product_id' => $product->id,
                    'variant_id' => null,
                    'warehouse_id' => $warehouseId,
                ],
                ['quantity' => 0, 'reserved_quantity' => 0]
            );

            // Floor at zero — a large negative adjustment (or two concurrent ones)
            // must not be able to drive a warehouse's stock below zero, which
            // would corrupt the availability math used by autoAllocate().
            $stock->update(['quantity' => max(0, $stock->quantity + $qty)]);
        });
    }

    /**
     * Atomically moves stock from one warehouse to another — previously the
     * only way to do this was two independent adjustStock() calls (separate
     * transactions, no shared atomicity: a failure between them left stock
     * deducted from the source but never added to the destination), and
     * adjustStock() floors an over-large deduction at zero instead of
     * rejecting it, so requesting a transfer larger than the source actually
     * holds silently created stock out of nowhere (source floored to 0,
     * destination still gets the full requested amount).
     *
     * Locks both rows in a consistent order (by warehouse_id) regardless of
     * transfer direction, matching CheckoutController's product-locking
     * pattern — otherwise two transfers moving stock in opposite directions
     * between the same two warehouses could lock in opposite order and
     * deadlock.
     */
    public function transferStock(
        int $fromWarehouseId,
        int $toWarehouseId,
        Product $product,
        int $quantity,
        ?int $variantId = null,
        ?string $reason = null,
        ?int $createdBy = null,
    ): WarehouseTransfer {
        if ($fromWarehouseId === $toWarehouseId) {
            throw ValidationException::withMessages(['to_warehouse_id' => 'Magazyn docelowy musi być inny niż źródłowy.']);
        }
        if ($quantity <= 0) {
            throw ValidationException::withMessages(['quantity' => 'Ilość musi być większa od zera.']);
        }

        return DB::transaction(function () use ($fromWarehouseId, $toWarehouseId, $product, $quantity, $variantId, $reason, $createdBy) {
            $lockOrder = $fromWarehouseId < $toWarehouseId
                ? [$fromWarehouseId, $toWarehouseId]
                : [$toWarehouseId, $fromWarehouseId];

            $locked = [];
            foreach ($lockOrder as $warehouseId) {
                $locked[$warehouseId] = ProductWarehouseStock::lockForUpdate()->firstOrCreate(
                    ['product_id' => $product->id, 'variant_id' => $variantId, 'warehouse_id' => $warehouseId],
                    ['quantity' => 0, 'reserved_quantity' => 0]
                );
            }

            $source = $locked[$fromWarehouseId];
            $destination = $locked[$toWarehouseId];

            if ($source->availableQuantity() < $quantity) {
                throw ValidationException::withMessages([
                    'quantity' => "Magazyn źródłowy ma dostępnych tylko {$source->availableQuantity()} szt. (reszta zarezerwowana lub brak w magazynie).",
                ]);
            }

            $source->decrement('quantity', $quantity);
            $destination->increment('quantity', $quantity);

            return WarehouseTransfer::create([
                'product_id' => $product->id,
                'variant_id' => $variantId,
                'from_warehouse_id' => $fromWarehouseId,
                'to_warehouse_id' => $toWarehouseId,
                'quantity' => $quantity,
                'reason' => $reason,
                'created_by' => $createdBy,
            ]);
        });
    }

    public function autoAllocate(Order $order): array
    {
        $allocations = [];

        foreach ($order->items as $item) {
            $product = $item->product ?? Product::find($item->product_id);
            if (!$product) {
                continue;
            }

            // Find warehouse with most stock
            $stock = ProductWarehouseStock::with('warehouse')
                ->where('product_id', $item->product_id)
                ->where('variant_id', $item->variant_id)
                ->whereHas('warehouse', fn ($q) => $q->where('is_active', true))
                ->orderByDesc('quantity')
                ->first();

            if ($stock && $stock->availableQuantity() >= $item->quantity) {
                $stock->increment('reserved_quantity', $item->quantity);
                $allocations[] = [
                    'order_item_id' => $item->id,
                    'warehouse_id' => $stock->warehouse_id,
                    'warehouse' => $stock->warehouse->name,
                    'quantity' => $item->quantity,
                    'allocated' => true,
                ];
            } else {
                $allocations[] = [
                    'order_item_id' => $item->id,
                    'warehouse_id' => null,
                    'warehouse' => null,
                    'quantity' => $item->quantity,
                    'allocated' => false,
                ];
            }
        }

        return $allocations;
    }
}
