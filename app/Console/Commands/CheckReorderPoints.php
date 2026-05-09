<?php

namespace App\Console\Commands;

use App\Mail\Tenant\LowStockAlertMail;
use App\Models\Landlord\Tenant;
use App\Models\Tenant\Product;
use App\Models\Tenant\User;
use App\Notifications\LowStockNotification;
use App\Services\PurchaseOrderService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class CheckReorderPoints extends Command
{
    protected $signature = 'inventory:check-reorder-points';

    protected $description = 'Check products below reorder point and create purchase orders automatically';

    public function handle(): int
    {
        $tenants = Tenant::all();

        foreach ($tenants as $tenant) {
            tenancy()->initialize($tenant);

            try {
                $this->checkCurrentTenant($tenant->id);
            } catch (\Throwable $e) {
                $this->error("[{$tenant->id}] Error: " . $e->getMessage());
            }

            tenancy()->end();
        }

        return self::SUCCESS;
    }

    /**
     * Runs against whichever tenant database is currently initialized.
     * Extracted from handle()'s loop body so it's directly callable in tests
     * without going through the landlord Tenant::all() + tenancy()->initialize()
     * dance (tests only migrate the tenant schema, not the landlord one).
     */
    public function checkCurrentTenant(string $tenantLabel): void
    {
        $products = Product::whereNotNull('reorder_point')
            ->where('track_stock', true)
            ->whereColumn('stock_quantity', '<=', 'reorder_point')
            ->get();

        foreach ($products as $product) {
            if ($product->supplier_id && $product->reorder_quantity > 0) {
                // Auto-create PO
                $service = app(PurchaseOrderService::class);
                $supplier = $product->supplier;

                if ($supplier && $supplier->is_active) {
                    $po = $service->create($supplier, [
                        'status' => 'draft',
                        'notes' => 'Auto-generated reorder for: ' . $product->name,
                        'items' => [[
                            'product_id' => $product->id,
                            'variant_id' => null,
                            'quantity' => $product->reorder_quantity,
                            'unit_cost' => (float) ($product->cost_price ?? $product->price),
                        ]],
                    ]);

                    $this->line("[{$tenantLabel}] Created PO {$po->po_number} for product: {$product->name} (qty: {$product->reorder_quantity})");
                } else {
                    Log::warning("[Reorder] Product #{$product->id} '{$product->name}' below reorder point but no active supplier.");
                    $this->warn("[{$tenantLabel}] Product '{$product->name}' needs reorder but has no active supplier.");
                }
            } else {
                Log::warning("[Reorder] Product #{$product->id} '{$product->name}' below reorder point ({$product->stock_quantity}/{$product->reorder_point}) — no supplier/reorder_quantity set.");
                $this->warn("[{$tenantLabel}] Alert: '{$product->name}' stock {$product->stock_quantity} <= reorder point {$product->reorder_point}. No auto-PO (missing supplier or reorder_quantity).");

                // No auto-PO possible here, so a manager needs to act manually —
                // previously this branch was a log line nobody reads.
                try {
                    // LowStockAlertMail::envelope() sets its own "to" from
                    // shop_email, so it's queued directly without Mail::to().
                    Mail::queue(new LowStockAlertMail($product, null, $product->stock_quantity));
                } catch (\Throwable $e) {
                    Log::warning("[Reorder] Low stock mail failed for product #{$product->id}: " . $e->getMessage());
                }

                try {
                    $managers = User::where('role', 'manager')->where('is_active', true)->get();
                    foreach ($managers as $manager) {
                        $manager->notify(new LowStockNotification($product, $product->stock_quantity));
                    }
                } catch (\Throwable $e) {
                    Log::warning("[Reorder] Low stock notification failed for product #{$product->id}: " . $e->getMessage());
                }
            }
        }
    }
}
