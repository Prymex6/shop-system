<?php

namespace App\Console\Commands;

use App\Jobs\SendScheduledReport;
use App\Models\Landlord\Tenant;
use Illuminate\Console\Command;
use Stancl\Tenancy\Facades\Tenancy;

class SendWeeklyReports extends Command
{
    protected $signature = 'shop:send-weekly-reports {--monthly : Send monthly report instead} {--tenant= : Specific tenant ID}';

    protected $description = 'Send weekly (or monthly) reports to shop managers';

    public function handle(): int
    {
        $period = $this->option('monthly') ? 'monthly' : 'weekly';
        $tenantId = $this->option('tenant');

        if ($tenantId) {
            $tenants = Tenant::where('id', $tenantId)->get();
        } else {
            $tenants = Tenant::all();
        }

        $dispatched = 0;

        foreach ($tenants as $tenant) {
            Tenancy::initialize($tenant);

            SendScheduledReport::dispatch($period);
            $dispatched++;

            Tenancy::end();
        }

        $this->info("Dispatched {$dispatched} scheduled report jobs ({$period}).");

        return self::SUCCESS;
    }
}
