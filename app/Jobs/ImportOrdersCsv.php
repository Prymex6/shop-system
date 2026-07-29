<?php

namespace App\Jobs;

use App\Services\OrderImportService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Http\UploadedFile;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ImportOrdersCsv implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public int $timeout = 300;

    public function __construct(
        public string $storedFilePath,
        public string $tenantId,
        public ?int $userId = null,
    ) {}

    public function handle(OrderImportService $importService): void
    {
        // See ImportCustomersCsv — storage_path('app/' . $path) computes the
        // wrong absolute path against the 'local' disk in Laravel 11+.
        $fullPath = Storage::disk('local')->path($this->storedFilePath);

        if (!file_exists($fullPath)) {
            Log::error('ImportOrdersCsv: file not found', ['path' => $fullPath]);

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

        Log::info('ImportOrdersCsv: import complete', [
            'tenant_id' => $this->tenantId,
            'imported' => $result['imported'],
            'skipped' => $result['skipped'],
            'errors' => $result['errors'],
        ]);

        Storage::disk('local')->delete($this->storedFilePath);
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('ImportOrdersCsv: job failed', ['error' => $exception->getMessage()]);
        Storage::disk('local')->delete($this->storedFilePath);
    }
}
