<?php

namespace App\Http\Controllers\Tenant\Manager;

use App\Http\Controllers\Controller;
use App\Models\Landlord\Tenant;
use App\Models\Tenant\Order;
use App\Models\Tenant\Setting;
use Carbon\Carbon;
use Inertia\Inertia;

class LicenseController extends Controller
{
    public function index()
    {
        // Get tenant info from landlord DB
        $tenantId = tenant('id');
        $tenantData = null;

        try {
            // Switch to central DB to get tenant plan/expiry
            tenancy()->end();
            $tenantModel = Tenant::with('plan')->find($tenantId);
            tenancy()->initialize($tenantModel);

            if ($tenantModel) {
                $tenantData = [
                    'name' => $tenantModel->name,
                    'status' => $tenantModel->status,
                    'license_ends_at' => $tenantModel->license_ends_at,
                    'plan' => $tenantModel->plan ? [
                        'name' => $tenantModel->plan->name,
                        'price' => $tenantModel->plan->price,
                        'max_orders_per_month' => $tenantModel->plan->max_orders_per_month,
                    ] : null,
                ];
            }
        } catch (\Exception $e) {
            // Fallback if cross-DB query fails
        }

        // Orders this month (in tenant DB) — using Warsaw timezone boundaries
        $tz = Setting::get('timezone', 'Europe/Warsaw');
        $monthStart = Carbon::now($tz)->startOfMonth()->setTimezone('UTC');
        $monthEnd = Carbon::now($tz)->endOfMonth()->setTimezone('UTC');
        $ordersThisMonth = Order::whereBetween('created_at', [$monthStart, $monthEnd])->count();

        return Inertia::render('Tenant/Manager/License', [
            'tenantData' => $tenantData,
            'ordersThisMonth' => $ordersThisMonth,
        ]);
    }
}
