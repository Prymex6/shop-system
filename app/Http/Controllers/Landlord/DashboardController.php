<?php

namespace App\Http\Controllers\Landlord;

use App\Http\Controllers\Controller;
use App\Models\Landlord\Tenant;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index()
    {
        $stats = [
            'total_tenants' => Tenant::count(),
            'active_tenants' => Tenant::where('status', 'active')->count(),
            'trial_tenants' => Tenant::where('status', 'trial')->count(),
            'suspended_tenants' => Tenant::where('status', 'suspended')->count(),
            'total_revenue' => $this->calculateMonthlyRevenue(),
            'recent_tenants' => Tenant::with('plan')
                ->latest()
                ->take(5)
                ->get(),
        ];

        return Inertia::render('Landlord/Dashboard', [
            'stats' => $stats,
        ]);
    }

    private function calculateMonthlyRevenue()
    {
        return Tenant::where('status', 'active')
            ->whereHas('plan')
            ->with('plan')
            ->get()
            ->sum(function ($tenant) {
                return $tenant->plan?->price ?? 0;
            });
    }
}
