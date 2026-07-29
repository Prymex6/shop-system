<?php

namespace App\Console\Commands;

use App\Jobs\CreateBackup;
use App\Models\Landlord\Tenant;
use Illuminate\Console\Command;

class CreateTenantBackup extends Command
{
    protected $signature = 'backup:tenant {tenant? : Tenant ID} {--type=full : Backup type (full)}';

    protected $description = 'Create a backup for a tenant';

    public function handle(): int
    {
        $tenantId = $this->argument('tenant');
        $type = $this->option('type');

        if ($tenantId) {
            $this->info("Dispatching backup job for tenant: {$tenantId}");
            CreateBackup::dispatch($type, $tenantId);
            $this->info('Backup job dispatched.');

            return self::SUCCESS;
        }

        // No tenant given — dispatch one job per tenant, matching every other
        // scheduled per-tenant command (abandoned carts, loyalty jobs, etc.).
        // Previously this branch ran a single synchronous backup for whatever
        // tenant happened to already be initialized, which is why this command
        // was never actually wired into the scheduler — running it there would
        // have backed up at most one tenant, not all of them.
        $tenants = Tenant::all();
        $this->info("Dispatching {$type} backup jobs for {$tenants->count()} tenant(s)...");

        foreach ($tenants as $tenant) {
            CreateBackup::dispatch($type, $tenant->id);
        }

        $this->info('Backup jobs dispatched.');

        return self::SUCCESS;
    }
}
