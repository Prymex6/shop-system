<?php

namespace App\Console\Commands;

use App\Models\Landlord\Tenant;
use App\Models\Tenant\Customer;
use App\Services\LoyaltyService;
use Illuminate\Console\Command;

class LoyaltyMonthlyBonus extends Command
{
    protected $signature = 'loyalty:monthly-bonus';

    protected $description = 'Award monthly tier bonuses to all eligible customers (runs for all tenants)';

    public function handle(): int
    {
        $tenants = Tenant::all();

        foreach ($tenants as $tenant) {
            tenancy()->initialize($tenant);

            try {
                $service = app(LoyaltyService::class);
                $count = 0;

                Customer::whereNotNull('loyalty_tier')->cursor()->each(function (Customer $customer) use ($service, &$count) {
                    $service->awardMonthlyBonus($customer);
                    $count++;
                });

                $this->line("[{$tenant->id}] Monthly bonus processed for {$count} customers");
            } catch (\Throwable $e) {
                $this->error("[{$tenant->id}] Error: " . $e->getMessage());
            }

            tenancy()->end();
        }

        return self::SUCCESS;
    }
}
