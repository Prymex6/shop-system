<?php

namespace App\Jobs;

use App\Services\CustomerImportService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Http\UploadedFile;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ImportCustomersCsv implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public int $timeout = 300;

    public function __construct(
        public string $storedFilePath,
        public string $tenantId,
        public ?int $userId = null,
    ) {}

    public function handle(CustomerImportService $importService): void
    {
        // Was storage_path('app/' . $path) — wrong in Laravel 11+, where the
        // 'local' disk's root is storage_path('app/private'), not
        // storage_path('app/') directly. That mismatch meant this always
        // computed a nonexistent path and every import silently did
        // nothing (logged "file not found", never surfaced to the manager
        // who just sees "import queued" and no error). Storage::disk()->path()
        // resolves correctly regardless of the disk's actual root.
        $fullPath = Storage::disk('local')->path($this->storedFilePath);

        if (!file_exists($fullPath)) {
            Log::error('ImportCustomersCsv: file not found', ['path' => $fullPath]);

            return;
        }

        $file = new UploadedFile(
            $fullPath,
            basename($fullPath),
            'text/csv',
            null,
            true
        );

        $result = $importService->import($file);

        Log::info('ImportCustomersCsv: import complete', [
            'tenant_id' => $this->tenantId,
            'imported' => $result['imported'],
            'skipped' => $result['skipped'],
            'errors' => $result['errors'],
        ]);

        Storage::disk('local')->delete($this->storedFilePath);
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('ImportCustomersCsv: job failed', ['error' => $exception->getMessage()]);
        Storage::disk('local')->delete($this->storedFilePath);
    }
}
