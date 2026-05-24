<?php

namespace App\Console\Commands;

use App\Models\Landlord\Tenant;
use App\Services\LoyaltyService;
use Illuminate\Console\Command;

class LoyaltyExpirePoints extends Command
{
    protected $signature = 'loyalty:expire-points';

    protected $description = 'Expire overdue loyalty points and send warnings for soon-to-expire points (runs for all tenants)';

    public function handle(): int
    {
        $tenants = Tenant::all();

        foreach ($tenants as $tenant) {
            tenancy()->initialize($tenant);

            try {
                $service = app(LoyaltyService::class);
                $expired = $service->expirePoints();
                $warned = $service->sendExpiryWarnings();

                $this->line("[{$tenant->id}] Expired: {$expired} records | Warned: {$warned} customers");
            } catch (\Throwable $e) {
                $this->error("[{$tenant->id}] Error: " . $e->getMessage());
            }

            tenancy()->end();
        }

        return self::SUCCESS;
    }
}
