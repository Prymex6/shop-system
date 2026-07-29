<?php

namespace App\Http\Controllers\Tenant\Manager;

use App\Http\Controllers\Controller;
use App\Jobs\CreateBackup;
use App\Services\BackupService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class BackupController extends Controller
{
    public function __construct(protected BackupService $backupService) {}

    public function index()
    {
        return Inertia::render('Tenant/Manager/Backups/Index', [
            'backups' => $this->backupService->list(),
        ]);
    }

    public function create(Request $request)
    {
        $tenantId = tenancy()->tenant?->id;
        CreateBackup::dispatch('full', $tenantId);

        return back()->with('success', __('messages.backup_queued'));
    }

    public function download(Request $request, string $filename)
    {
        return $this->backupService->download($filename);
    }

    public function destroy(string $filename)
    {
        $this->backupService->delete($filename);

        return back()->with('success', __('messages.backup_deleted'));
    }
}
