<?php

namespace App\Console\Commands;

use App\Services\TenantModificationService;
use Illuminate\Console\Command;

class TenantModClearCommand extends Command
{
    protected $signature = 'tenant:mod:clear
                            {tenant? : Tenant ID (omit to clear ALL tenants)}';

    protected $description = 'Clear applied tenant modifications cache';

    public function handle(TenantModificationService $service): int
    {
        $tenantId = $this->argument('tenant');

        if ($tenantId) {
            $service->clearForTenant($tenantId);
            $this->info("Cleared modifications cache for tenant: {$tenantId}");
        } else {
            if (!$this->confirm('Clear modifications cache for ALL tenants?')) {
                $this->info('Aborted.');

                return self::SUCCESS;
            }
            $service->clearAll();
            $this->info('Cleared all modifications cache.');
        }

        return self::SUCCESS;
    }
}
