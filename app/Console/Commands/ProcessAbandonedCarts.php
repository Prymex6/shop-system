<?php

namespace App\Console\Commands;

use App\Jobs\SendAbandonedCartReminder;
use App\Models\Landlord\Tenant;
use App\Models\Tenant\AbandonedCart;
use Illuminate\Console\Command;
use Stancl\Tenancy\Facades\Tenancy;

class ProcessAbandonedCarts extends Command
{
    protected $signature = 'shop:process-abandoned-carts {--tenant= : Specific tenant ID}';

    protected $description = 'Send abandoned cart reminder emails to customers';

    public function handle(): int
    {
        $tenantId = $this->option('tenant');

        if ($tenantId) {
            $tenants = Tenant::where('id', $tenantId)->get();
        } else {
            $tenants = Tenant::all();
        }

        $total = 0;

        foreach ($tenants as $tenant) {
            Tenancy::initialize($tenant);

            $carts = AbandonedCart::eligibleForReminder(60)->get();

            foreach ($carts as $cart) {
                SendAbandonedCartReminder::dispatch($cart);
                $total++;
            }

            Tenancy::end();
        }

        $this->info("Dispatched {$total} abandoned cart reminder jobs.");

        return self::SUCCESS;
    }
}
