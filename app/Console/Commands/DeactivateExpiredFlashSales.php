<?php

namespace App\Console\Commands;

use App\Models\Landlord\Tenant;
use App\Models\Tenant\FlashSale;
use Illuminate\Console\Command;

class DeactivateExpiredFlashSales extends Command
{
    protected $signature = 'flash-sales:deactivate-expired';

    protected $description = 'Deactivate flash sales that have passed their end date or reached max orders';

    public function handle(): int
    {
        $tenants = Tenant::all();

        foreach ($tenants as $tenant) {
            tenancy()->initialize($tenant);

            try {
                $count = FlashSale::where('is_active', true)
                    ->where(function ($q) {
                        $q->where('ends_at', '<', now())
                            ->orWhere(function ($q2) {
                                $q2->whereNotNull('max_orders')
                                    ->whereColumn('orders_count', '>=', 'max_orders');
                            });
                    })
                    ->update(['is_active' => false]);

                if ($count > 0) {
                    $this->line("[{$tenant->id}] Deactivated {$count} expired flash sale(s).");
                }
            } catch (\Throwable $e) {
                $this->error("[{$tenant->id}] Error: " . $e->getMessage());
            }

            tenancy()->end();
        }

        return self::SUCCESS;
    }
}
