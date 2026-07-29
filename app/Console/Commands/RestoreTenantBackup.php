<?php

namespace App\Console\Commands;

use App\Models\Landlord\Tenant;
use App\Services\BackupService;
use Illuminate\Console\Command;

/**
 * BackupService::restore() previously had no route, command, or test
 * calling it at all — "restore" was 100% dead code, meaning a real disaster
 * (dropped table, bad migration, accidental data loss) had no actual way
 * back despite backups being created. A console command — not a web
 * button — is the deliberate entry point on purpose: restoring truncates
 * every table in the tenant's database and overwrites its public files,
 * which shouldn't be one accidental click away in the manager panel.
 */
class RestoreTenantBackup extends Command
{
    protected $signature = 'backup:restore {tenant : Tenant ID} {filename : Backup filename, e.g. backup_2026-08-11_12-00-00_full.zip}';

    protected $description = 'Restore a tenant from a backup file — DESTRUCTIVE, overwrites the tenant\'s current DB and public files';

    public function handle(BackupService $backupService): int
    {
        $tenantId = $this->argument('tenant');
        $filename = $this->argument('filename');

        $tenant = Tenant::find($tenantId);
        if (!$tenant) {
            $this->error("Tenant not found: {$tenantId}");

            return self::FAILURE;
        }

        $this->warn("This will PERMANENTLY OVERWRITE all data for tenant '{$tenant->name}' ({$tenantId})");
        $this->warn("with the contents of backup: {$filename}");

        if (!$this->confirm('Are you absolutely sure you want to continue?')) {
            $this->info('Aborted.');

            return self::SUCCESS;
        }

        if ($this->ask('Type the tenant ID to confirm') !== $tenantId) {
            $this->error('Tenant ID did not match — aborted.');

            return self::FAILURE;
        }

        tenancy()->initialize($tenant);

        try {
            $this->info('Restoring...');
            $backupService->restore($filename);
            $this->info('Restore complete.');
        } catch (\Throwable $e) {
            $this->error('Restore failed: ' . $e->getMessage());

            return self::FAILURE;
        } finally {
            tenancy()->end();
        }

        return self::SUCCESS;
    }
}
