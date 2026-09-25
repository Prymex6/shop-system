<?php

namespace App\Http\Controllers\Tenant\Manager;

use App\Http\Controllers\Controller;
use App\Models\Tenant\Category;
use App\Models\Tenant\Order;
use App\Models\Tenant\Product;
use App\Models\Tenant\Setting;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        // Helper: scope for counting revenue (online paid OR cash/card completed)
        $revenueScope = fn ($q) => $q->where(function ($sub) {
            $sub->where('payment_status', 'paid')
                ->orWhere(function ($inner) {
                    $inner->where('payment_method', 'cash_on_delivery')
                        ->where('status', 'completed');
                });
        });

        $tz = Setting::get('timezone', 'Europe/Warsaw');

        // Statystyki dzienne
        $todayStart = Carbon::now($tz)->startOfDay()->setTimezone('UTC');
        $todayEnd = Carbon::now($tz)->endOfDay()->setTimezone('UTC');
        $todayOrders = Order::whereBetween('created_at', [$todayStart, $todayEnd])->count();
        $todayRevenue = Order::whereBetween('created_at', [$todayStart, $todayEnd])
            ->tap($revenueScope)
            ->sum('total');

        // Statystyki tygodniowe
        $weekStart = Carbon::now($tz)->startOfWeek()->setTimezone('UTC');
        $weekOrders = Order::where('created_at', '>=', $weekStart)->count();
        $weekRevenue = Order::where('created_at', '>=', $weekStart)
            ->tap($revenueScope)
            ->sum('total');

        // This month
        $monthStart = Carbon::now($tz)->startOfMonth()->setTimezone('UTC');
        $monthOrders = Order::where('created_at', '>=', $monthStart)->count();
        $monthRevenue = Order::where('created_at', '>=', $monthStart)
            ->tap($revenueScope)
            ->sum('total');

        // The most recent orders
        $recentOrders = Order::with(['items', 'customer'])
            ->latest()
            ->limit(10)
            ->get();

        // Date range for chart (default: last 30 days; custom via ?date_from=&date_to=)
        $tzOffset = Carbon::now($tz)->format('P');
        $chartDays = 30;
        $customRange = false;

        // Both are set together or not at all: a range whose dates would not
        // parse, or that runs backwards, or that covers more than a year,
        // falls back to the last thirty days rather than being half-applied.
        $chartFrom = null;
        $chartTo = null;

        if ($request->filled('date_from') && $request->filled('date_to')) {
            try {
                $from = Carbon::createFromFormat('Y-m-d', $request->date_from, $tz)->startOfDay()->setTimezone('UTC');
                $to = Carbon::createFromFormat('Y-m-d', $request->date_to, $tz)->endOfDay()->setTimezone('UTC');

                if ($from->lte($to) && $from->diffInDays($to) <= 366) {
                    $chartFrom = $from;
                    $chartTo = $to;
                }
            } catch (\Exception) {
                // Leaves both null, which is the default range.
            }
        }

        if ($chartFrom !== null && $chartTo !== null) {
            $chartDays = $chartFrom->diffInDays($chartTo) + 1;
            $customRange = true;
        } else {
            $chartTo = Carbon::now($tz)->endOfDay()->setTimezone('UTC');
            $chartFrom = Carbon::now($tz)->subDays(29)->startOfDay()->setTimezone('UTC');
        }

        // Najpopularniejsze produkty (w zakresie dat wykresu)
        $popularProducts = DB::table('order_items')
            ->select('name', DB::raw('SUM(quantity) as total_quantity'), DB::raw('SUM(price * quantity) as total_revenue'))
            ->whereIn('order_id', function ($query) use ($chartFrom, $chartTo) {
                $query->select('id')->from('orders')
                    ->whereBetween('created_at', [$chartFrom, $chartTo]);
            })
            ->groupBy('name')
            ->orderByDesc('total_quantity')
            ->limit(5)
            ->get();

        // Broken down by payment method
        $ordersByType = Order::select('payment_method', DB::raw('count(*) as count'))
            ->where('created_at', '>=', $monthStart->copy())
            ->whereNotNull('payment_method')
            ->groupBy('payment_method')
            ->get()
            ->pluck('count', 'payment_method');

        // How many products and categories there are
        $productsCount = Product::count();
        $categoriesCount = Category::count();
        $activeProductsCount = Product::where('is_published', true)->count();

        // Daily revenue chart — compatible with MySQL and SQLite
        $dateExpr = DB::connection()->getDriverName() === 'sqlite'
            ? DB::raw('DATE(created_at) as date')
            : DB::raw("DATE(CONVERT_TZ(created_at, '+00:00', '{$tzOffset}')) as date");

        $dailyRevenue = Order::select(
            $dateExpr,
            DB::raw('SUM(total) as revenue'),
            DB::raw('COUNT(*) as orders_count')
        )
            ->whereBetween('created_at', [$chartFrom, $chartTo])
            ->tap($revenueScope)
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->keyBy('date');

        // Fill in missing days with zeros
        $revenueChart = [];
        for ($i = $chartDays - 1; $i >= 0; $i--) {
            $day = Carbon::instance($chartTo)->setTimezone($tz)->subDays($i)->format('Y-m-d');
            $revenueChart[] = [
                'date' => $day,
                'label' => Carbon::parse($day)->format('d.m'),
                'revenue' => (float) ($dailyRevenue[$day]->revenue ?? 0),
                'orders' => (int) ($dailyRevenue[$day]->orders_count ?? 0),
            ];
        }

        // Hourly order heatmap — compatible with MySQL and SQLite
        $hourExpr = DB::connection()->getDriverName() === 'sqlite'
            ? DB::raw("CAST(strftime('%H', created_at) AS INTEGER) as hour")
            : DB::raw("HOUR(CONVERT_TZ(created_at, '+00:00', '{$tzOffset}')) as hour");

        $hourlyData = Order::select($hourExpr, DB::raw('COUNT(*) as count'))
            ->whereBetween('created_at', [$chartFrom, $chartTo])
            ->groupBy('hour')
            ->get()
            ->keyBy('hour');

        $hourlyHeatmap = array_map(fn ($h) => [
            'hour' => $h,
            'label' => str_pad($h, 2, '0', STR_PAD_LEFT) . ':00',
            'count' => (int) ($hourlyData[$h]->count ?? 0),
        ], range(0, 23));

        return Inertia::render('Tenant/Manager/Dashboard', [
            'stats' => [
                'today' => [
                    'orders' => $todayOrders,
                    'revenue' => (float) $todayRevenue,
                ],
                'week' => [
                    'orders' => $weekOrders,
                    'revenue' => (float) $weekRevenue,
                ],
                'month' => [
                    'orders' => $monthOrders,
                    'revenue' => (float) $monthRevenue,
                ],
            ],
            'recentOrders' => $recentOrders,
            'popularProducts' => $popularProducts,
            'ordersByType' => $ordersByType,
            'catalog' => [
                'products' => $productsCount,
                'categories' => $categoriesCount,
                'active' => $activeProductsCount,
            ],
            'revenueChart' => $revenueChart,
            'hourlyHeatmap' => $hourlyHeatmap,
            'filters' => [
                'date_from' => $request->date_from ?? Carbon::instance($chartFrom)->setTimezone($tz)->format('Y-m-d'),
                'date_to' => $request->date_to ?? Carbon::now($tz)->format('Y-m-d'),
            ],
        ]);
    }
}
