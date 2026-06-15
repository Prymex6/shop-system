<?php

namespace App\Http\Controllers\Tenant\Staff;

use App\Events\StaffReportCreated;
use App\Http\Controllers\Controller;
use App\Models\Tenant\StaffReport;
use Illuminate\Http\Request;
use Inertia\Inertia;

class StaffReportController extends Controller
{
    public function index()
    {
        $user = auth('tenant')->user();
        $reports = StaffReport::where('staff_user_id', $user->id)
            ->orderByDesc('created_at')
            ->get();

        return Inertia::render('Tenant/Staff/Report', [
            'reports' => $reports,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:200'],
            'message' => ['required', 'string', 'max:2000'],
        ]);

        $user = auth('tenant')->user();

        $report = StaffReport::create([
            'staff_user_id' => $user->id,
            'role' => $user->role,
            'title' => $validated['title'],
            'message' => $validated['message'],
            'status' => 'new',
        ]);

        event(new StaffReportCreated($report));

        return back()->with('success', __('messages.report_sent'));
    }
}
