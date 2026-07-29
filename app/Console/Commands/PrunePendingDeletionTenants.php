<?php

namespace App\Console\Commands;

use App\Models\Landlord\Tenant;
use App\Services\BackupService;
use App\Services\CloudflareService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Finishes what TenantController::destroy() only started: tenants flagged
 * 'pending_deletion' more than GRACE_DAYS ago get one last automatic backup
 * (survives, since the deletion pipeline only drops the database, not tenant
 * storage) and then their database is actually dropped. Run daily.
 */
class PrunePendingDeletionTenants extends Command
{
    protected $signature = 'tenants:prune-pending-deletion';

    protected $description = 'Permanently delete tenants that have been pending_deletion past the grace period';

    private const GRACE_DAYS = 7;

    public function handle(): int
    {
        $tenants = Tenant::where('status', 'pending_deletion')
            ->whereNotNull('deletion_requested_at')
            ->where('deletion_requested_at', '<=', now()->subDays(self::GRACE_DAYS))
            ->get();

        foreach ($tenants as $tenant) {
            $name = $tenant->name;

            try {
                tenancy()->initialize($tenant);
                try {
                    app(BackupService::class)->create('full');
                } catch (\Throwable $e) {
                    Log::warning("Pre-deletion backup failed for tenant {$tenant->id}: " . $e->getMessage());
                }
                tenancy()->end();

                $cloudflare = app(CloudflareService::class);
                if ($cloudflare->isConfigured()) {
                    $dnsRecordId = data_get($tenant->data, 'cloudflare_dns_id');
                    if ($dnsRecordId) {
                        $cloudflare->removeSubdomain($dnsRecordId);
                    }
                }

                $tenant->delete(); // Drops the database via the TenantDeleted job pipeline

                $this->info("Deleted tenant '{$name}' ({$tenant->id}) after grace period.");
                Log::info('Tenant pruned after pending_deletion grace period', ['tenant_id' => $tenant->id, 'name' => $name]);
            } catch (\Throwable $e) {
                $this->error("Failed to delete tenant '{$name}' ({$tenant->id}): " . $e->getMessage());
                Log::error('Tenant prune failed', ['tenant_id' => $tenant->id, 'error' => $e->getMessage()]);
            }
        }

        return self::SUCCESS;
    }
}
