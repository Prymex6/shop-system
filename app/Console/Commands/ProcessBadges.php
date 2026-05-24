<?php

namespace App\Console\Commands;

use App\Models\Landlord\Tenant;
use App\Models\Tenant\Customer;
use App\Services\BadgeService;
use Illuminate\Console\Command;

class ProcessBadges extends Command
{
    protected $signature = 'loyalty:process-badges';

    protected $description = 'Check and award badges to all customers across all tenants';

    public function handle(): int
    {
        $tenants = Tenant::all();

        foreach ($tenants as $tenant) {
            tenancy()->initialize($tenant);

            try {
                $service = app(BadgeService::class);
                $customers = Customer::all();
                $total = 0;

                foreach ($customers as $customer) {
                    $awarded = $service->checkAndAward($customer);
                    $total += count($awarded);
                }

                if ($total > 0) {
                    $this->line("[{$tenant->id}] Awarded {$total} badge(s) to customers.");
                }
            } catch (\Throwable $e) {
                $this->error("[{$tenant->id}] Error: " . $e->getMessage());
            }

            tenancy()->end();
        }

        return self::SUCCESS;
    }
}
