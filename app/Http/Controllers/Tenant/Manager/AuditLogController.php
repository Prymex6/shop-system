<?php

namespace App\Http\Controllers\Tenant\Manager;

use App\Http\Controllers\Controller;
use App\Models\Tenant\AuditLog;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AuditLogController extends Controller
{
    public function index(Request $request)
    {
        $query = AuditLog::query()
            ->when($request->user_type, fn ($q) => $q->where('user_type', $request->user_type))
            ->when($request->user_id, fn ($q) => $q->where('user_id', $request->user_id))
            ->when($request->action, fn ($q) => $q->where('action', 'like', '%' . $request->action . '%'))
            ->when($request->date_from, fn ($q) => $q->whereDate('created_at', '>=', $request->date_from))
            ->when($request->date_to, fn ($q) => $q->whereDate('created_at', '<=', $request->date_to))
            ->latest()
            ->paginate((int) ($request->per_page ?? 50))
            ->withQueryString();

        return Inertia::render('Tenant/Manager/AuditLog/Index', [
            'logs' => $query,
            'filters' => $request->only(['user_type', 'user_id', 'action', 'date_from', 'date_to', 'per_page']),
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        // cursor() — this is one of the fastest-growing tables in the app
        // (written on nearly every state-changing action), and unlike
        // index() above it has no pagination/date-range requirement, so it
        // had no cap on how much could be pulled into memory at once.
        $logs = AuditLog::query()
            ->when($request->user_type, fn ($q) => $q->where('user_type', $request->user_type))
            ->when($request->user_id, fn ($q) => $q->where('user_id', $request->user_id))
            ->when($request->action, fn ($q) => $q->where('action', 'like', '%' . $request->action . '%'))
            ->when($request->date_from, fn ($q) => $q->whereDate('created_at', '>=', $request->date_from))
            ->when($request->date_to, fn ($q) => $q->whereDate('created_at', '<=', $request->date_to))
            ->latest()
            ->cursor();

        return response()->streamDownload(function () use ($logs) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['ID', 'Użytkownik', 'Typ', 'Akcja', 'Model', 'Model ID', 'IP', 'Data']);
            foreach ($logs as $log) {
                fputcsv($out, [
                    $log->id,
                    $log->user_id,
                    $log->user_type,
                    $log->action,
                    $log->model_type,
                    $log->model_id,
                    $log->ip_address,
                    $log->created_at,
                ]);
            }
            fclose($out);
        }, 'audit-log-' . now()->format('Y-m-d') . '.csv', [
            'Content-Type' => 'text/csv',
        ]);
    }
}
