<?php

namespace App\Http\Controllers\Tenant\Manager;

use App\Http\Controllers\Controller;
use App\Models\Tenant\User;
use App\Services\StaffPerformanceService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;

class StaffPerformanceController extends Controller
{
    public function __construct(protected StaffPerformanceService $service) {}

    public function index(Request $request)
    {
        $from = $request->from ? Carbon::parse($request->from)->startOfDay() : Carbon::now()->subDays(30)->startOfDay();
        $to = $request->to ? Carbon::parse($request->to)->endOfDay() : Carbon::now()->endOfDay();

        $staff = User::where('role', '!=', 'manager')->get();
        $metrics = $staff->map(fn ($s) => $this->service->getMetrics($s, $from, $to));

        return Inertia::render('Tenant/Manager/StaffPerformance/Index', [
            'metrics' => $metrics,
            'periodFrom' => $from->toDateString(),
            'periodTo' => $to->toDateString(),
        ]);
    }
}
