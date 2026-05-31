<?php

namespace App\Console\Commands;

use App\Jobs\SendWishlistAlert;
use App\Models\Landlord\Tenant;
use App\Models\Tenant\Wishlist;
use Illuminate\Console\Command;
use Stancl\Tenancy\Facades\Tenancy;

class ProcessWishlistAlerts extends Command
{
    protected $signature = 'wishlist:process-alerts {--tenant= : Specific tenant ID}';

    protected $description = 'Notify customers when a wishlisted product drops in price or comes back in stock';

    public function handle(): int
    {
        $tenantId = $this->option('tenant');
        $tenants = $tenantId ? Tenant::where('id', $tenantId)->get() : Tenant::all();

        $total = 0;

        foreach ($tenants as $tenant) {
            Tenancy::initialize($tenant);

            $items = Wishlist::with('product')
                ->where(function ($q) {
                    $q->where('price_drop_notified', false)->orWhere('restock_notified', false);
                })
                ->get();

            foreach ($items as $item) {
                $total += static::processItem($item);
            }

            Tenancy::end();
        }

        $this->info("Dispatched {$total} wishlist alert jobs.");

        return self::SUCCESS;
    }

    /**
     * Pure per-item decision logic, split out from the tenant-iteration loop
     * above so it's directly unit-testable without a landlord DB — the loop
     * itself needs App\Models\Landlord\Tenant (a real "class not found" bug
     * here went undetected for every command using this pattern, since
     * every existing test in the suite only ever unit-tests the dispatched
     * jobs, never the command that's actually supposed to dispatch them).
     * Returns the number of jobs dispatched for this item (0, 1, or 2).
     */
    public static function processItem(Wishlist $item): int
    {
        $product = $item->product;
        if (!$product) {
            return 0;
        }

        $dispatched = 0;

        if (
            !$item->price_drop_notified
            && $item->price_at_added !== null
            && (float) $product->price < (float) $item->price_at_added
        ) {
            SendWishlistAlert::dispatch($item, 'price_drop');
            $dispatched++;
        }

        $inStock = $product->isInStock();

        if (!$item->restock_notified && $item->was_out_of_stock && $inStock) {
            SendWishlistAlert::dispatch($item, 'restock');
            $dispatched++;
        }

        // Keep the out-of-stock marker current so a later restock (after
        // being wishlisted while in stock, then going out, then back) is
        // still detected — restock only ever fires from a genuine
        // out-of-stock state, never for an item that was always in stock.
        if (!$inStock && !$item->was_out_of_stock) {
            $item->update(['was_out_of_stock' => true]);
        }

        return $dispatched;
    }
}
