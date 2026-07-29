<?php

namespace App\Console\Commands;

use App\Models\Landlord\Tenant;
use App\Services\TenantModificationService;
use Illuminate\Console\Command;

class TenantModApplyCommand extends Command
{
    protected $signature = 'tenant:mod:apply
                            {tenant? : Tenant ID (omit for all active tenants)}
                            {--force : Re-apply even if already applied}';

    protected $description = 'Apply tenant modifications (similar to OpenCart OCMod)';

    public function handle(TenantModificationService $service): int
    {
        $tenantId = $this->argument('tenant');

        if ($tenantId) {
            $tenants = Tenant::on('central')->where('id', $tenantId)->get();

            if ($tenants->isEmpty()) {
                $this->error("Tenant '{$tenantId}' not found.");

                return self::FAILURE;
            }
        } else {
            $tenants = Tenant::on('central')->where('status', '!=', 'suspended')->get();
            $this->info("Applying modifications for {$tenants->count()} tenants...");
        }

        foreach ($tenants as $tenant) {
            $this->line("→ <fg=cyan>{$tenant->name}</> ({$tenant->id})");

            $results = $service->applyForTenant($tenant->id);

            if (empty($results)) {
                $this->line('  <fg=gray>No active modifications.</>');

                continue;
            }

            foreach ($results as $result) {
                if ($result['status'] === 'ok') {
                    $fileCount = count($result['files']);
                    $this->line("  <fg=green>✓</> {$result['modification']} – {$fileCount} file(s) patched");
                    foreach ($result['files'] as $file) {
                        $this->line("    <fg=gray>  {$file}</>");
                    }
                } else {
                    $this->line("  <fg=red>✗</> {$result['modification']} – {$result['message']}");
                }
            }
        }

        $this->newLine();
        $this->info('Done.');

        return self::SUCCESS;
    }
}
