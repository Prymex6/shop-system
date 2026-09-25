<?php

namespace App\Http\Controllers\Tenant\Manager;

use App\Http\Controllers\Controller;
use App\Models\Tenant\StaffReport;
use App\Support\Csv;
use Illuminate\Http\Request;
use Inertia\Inertia;

class StaffReportsController extends Controller
{
    public function index(Request $request)
    {
        $query = StaffReport::with('staffUser')
            ->orderByDesc('created_at');

        if ($request->filled('role')) {
            $query->where('role', $request->role);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $reports = $query->paginate(20)->withQueryString();
        $newCount = StaffReport::where('status', 'new')->count();

        return Inertia::render('Tenant/Manager/StaffReports', [
            'reports' => $reports,
            'newCount' => $newCount,
            'filters' => $request->only(['role', 'status', 'date_from', 'date_to']),
        ]);
    }

    public function markRead(StaffReport $report)
    {
        $report->update(['status' => 'read']);

        return back()->with('success', 'Raport oznaczony jako przeczytany.');
    }

    public function markAllRead()
    {
        StaffReport::where('status', 'new')->update(['status' => 'read']);

        return back()->with('success', 'Wszystkie raporty oznaczone jako przeczytane.');
    }

    public function export(Request $request)
    {
        $query = StaffReport::with('staffUser')->orderByDesc('created_at');

        if ($request->filled('role')) {
            $query->where('role', $request->role);
        }
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $reports = $query->get();

        $csv = __('messages.csv_staff_reports_header') . "\n";
        foreach ($reports as $r) {
            $csv .= implode(',', [
                $r->id,
                Csv::field($r->staffUser?->name ?? '–'),
                $r->role,
                Csv::field($r->title),
                Csv::field($r->message),
                $r->status,
                $r->created_at->format('Y-m-d H:i'),
            ]) . "\n";
        }

        return response($csv, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="raporty-' . now()->format('Y-m-d') . '.csv"',
        ]);
    }
}
