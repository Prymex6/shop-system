<?php

namespace App\Jobs;

use App\Services\BackupService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class CreateBackup implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 300;

    public int $tries = 3;

    public function __construct(
        public string $type = 'full',
        public ?string $tenantId = null,
    ) {}

    public function handle(BackupService $backupService): void
    {
        try {
            if ($this->tenantId) {
                tenancy()->initialize($this->tenantId);
            }

            $result = $backupService->create($this->type);
            Log::info('Backup created', $result);
        } catch (\Exception $e) {
            Log::error('CreateBackup job failed: ' . $e->getMessage());
            throw $e;
        } finally {
            // When dispatched from console (no tenant context at dispatch time),
            // stancl/tenancy's queue payload carries no tenant_id, so its normal
            // end-of-job tenancy cleanup never fires for this self-initiated
            // tenancy() call — leaving the tenant DB/filesystem context active
            // for whatever this worker process runs next.
            if ($this->tenantId) {
                tenancy()->end();
            }
        }
    }

    /**
     * Backups are the safety net the rest of this app's dangerous operations
     * (tenant deletion, etc.) rely on — a silently-failing backup job with no
     * operator-visible signal beyond grepping logs is itself a risk. This runs
     * after all retries are exhausted.
     */
    public function failed(\Throwable $exception): void
    {
        Log::critical('CreateBackup: job failed permanently', [
            'type' => $this->type,
            'tenant_id' => $this->tenantId,
            'error' => $exception->getMessage(),
        ]);
    }
}
