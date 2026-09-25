<?php

namespace App\Http\Controllers\Tenant\Manager;

use App\Http\Controllers\Controller;
use App\Models\Tenant\Customer;
use App\Models\Tenant\Order;
use App\Models\Tenant\OrderItem;
use App\Support\Csv;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class ReportController extends Controller
{
    /**
     * Display reports dashboard
     */
    public function index(Request $request): Response
    {
        $period = $request->get('period', 'week'); // day, week, month, year, custom
        $startDate = $request->get('start_date');
        $endDate = $request->get('end_date');

        [$start, $end] = $this->getDateRange($period, $startDate, $endDate);

        // Sales summary
        $salesData = $this->getSalesSummary($start, $end);

        // Revenue chart data
        $revenueChartData = $this->getRevenueChartData($start, $end, $period);

        // Top products
        $topProducts = $this->getTopProducts($start, $end, 10);

        // Order statistics
        $orderStats = $this->getOrderStatistics($start, $end);

        // Peak hours
        $peakHours = $this->getPeakHours($start, $end);

        // Payment methods breakdown
        $paymentMethods = $this->getPaymentMethodsBreakdown($start, $end);

        return Inertia::render('Tenant/Manager/Reports/Index', [
            'period' => $period,
            'startDate' => $start->format('Y-m-d'),
            'endDate' => $end->format('Y-m-d'),
            'summary' => $salesData,
            'salesData' => $salesData,
            'revenueChart' => $revenueChartData,
            'topProducts' => $topProducts,
            'orderStats' => $orderStats,
            'peakHours' => $peakHours,
            'paymentMethods' => $paymentMethods,
        ]);
    }

    /**
     * Get date range based on period
     */
    protected function getDateRange(string $period, ?string $startDate, ?string $endDate): array
    {
        if ($period === 'custom' && $startDate && $endDate) {
            try {
                $start = Carbon::parse($startDate)->startOfDay();
                $end = Carbon::parse($endDate)->endOfDay();
            } catch (\Exception) {
                // Malformed input — fall through to the default range below
                // instead of a 500, and instead of running an ungrouped
                // whereBetween/get() query over an unbounded span.
                $start = null;
            }

            if (isset($start) && $start->diffInDays($end) <= 730) {
                return [$start, $end];
            }
        }

        $end = Carbon::now()->endOfDay();

        $start = match ($period) {
            'day' => Carbon::now()->startOfDay(),
            'week' => Carbon::now()->subDays(7)->startOfDay(),
            'month' => Carbon::now()->subDays(30)->startOfDay(),
            'year' => Carbon::now()->subYear()->startOfDay(),
            default => Carbon::now()->subDays(7)->startOfDay(),
        };

        return [$start, $end];
    }

    /**
     * Get sales summary
     */
    protected function getSalesSummary(Carbon $start, Carbon $end): array
    {
        // Aggregate in SQL instead of loading every matching order into a
        // Collection — for a wide date range this scales with the whole
        // orders table instead of returning a handful of numbers.
        $row = Order::whereBetween('created_at', [$start, $end])
            ->where('payment_status', 'paid')
            ->selectRaw('COUNT(*) as total_orders, COALESCE(SUM(total), 0) as total_revenue, COALESCE(SUM(discount), 0) as total_discount')
            ->first();

        $totalRevenue = (float) $row->total_revenue;
        $totalOrders = (int) $row->total_orders;
        $averageOrderValue = $totalOrders > 0 ? $totalRevenue / $totalOrders : 0;
        $totalDiscount = (float) $row->total_discount;

        return [
            'total_revenue' => $totalRevenue,
            'total_orders' => $totalOrders,
            'average_order_value' => $averageOrderValue,
            'total_discount' => $totalDiscount,
        ];
    }

    /**
     * Get revenue chart data
     */
    protected function getRevenueChartData(Carbon $start, Carbon $end, string $period): array
    {
        $isSqlite = DB::connection()->getDriverName() === 'sqlite';

        $dateFormat = match ($period) {
            'day' => '%Y-%m-%d %H:00:00',
            'week', 'month' => '%Y-%m-%d',
            'year' => '%Y-%m',
            default => '%Y-%m-%d',
        };

        if ($isSqlite) {
            $groupBy = match ($period) {
                'day' => DB::raw("strftime('%H', created_at)"),
                'year' => DB::raw("strftime('%Y-%m', created_at)"),
                default => DB::raw('DATE(created_at)'),
            };
            $dateExpr = DB::raw("strftime('$dateFormat', created_at) as date");
        } else {
            $groupBy = match ($period) {
                'day' => 'HOUR(created_at)',
                'week', 'month' => 'DATE(created_at)',
                'year' => "DATE_FORMAT(created_at, '%Y-%m')",
                default => 'DATE(created_at)',
            };
            $dateExpr = DB::raw("DATE_FORMAT(created_at, '$dateFormat') as date");
        }

        $data = Order::whereBetween('created_at', [$start, $end])
            ->where('payment_status', 'paid')
            ->select(
                $dateExpr,
                DB::raw('SUM(total) as revenue'),
                DB::raw('COUNT(*) as orders')
            )
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        $labels = [];
        $revenue = [];
        $orderCount = [];

        foreach ($data as $item) {
            $labels[] = $this->formatChartLabel($item->date, $period);
            $revenue[] = (float) $item->revenue;
            $orderCount[] = $item->orders;
        }

        return [
            'labels' => $labels,
            'revenue' => $revenue,
            'orders' => $orderCount,
        ];
    }

    /**
     * Format chart label based on period
     */
    protected function formatChartLabel(string $date, string $period): string
    {
        $carbon = Carbon::parse($date);

        return match ($period) {
            'day' => $carbon->format('H:00'),
            'week', 'month' => $carbon->format('d.m'),
            'year' => $carbon->format('M Y'),
            default => $carbon->format('d.m'),
        };
    }

    /**
     * Get top products
     */
    protected function getTopProducts(Carbon $start, Carbon $end, int $limit = 10): array
    {
        return OrderItem::join('orders', 'order_items.order_id', '=', 'orders.id')
            ->whereBetween('orders.created_at', [$start, $end])
            ->where('orders.payment_status', 'paid')
            ->select(
                'order_items.name',
                'order_items.variant_label',
                DB::raw('SUM(order_items.quantity) as total_quantity'),
                DB::raw('SUM(order_items.quantity * order_items.price) as total_revenue')
            )
            ->groupBy('order_items.name', 'order_items.variant_label')
            ->orderByDesc('total_quantity')
            ->limit($limit)
            ->get()
            ->map(function ($item) {
                return [
                    'name' => $item->name,
                    'variant_label' => $item->variant_label,
                    'quantity' => $item->total_quantity,
                    'revenue' => (float) $item->total_revenue,
                ];
            })
            ->toArray();
    }

    /**
     * Get order statistics
     */
    protected function getOrderStatistics(Carbon $start, Carbon $end): array
    {
        $orders = Order::whereBetween('created_at', [$start, $end]);

        $byType = $orders->clone()
            ->select('fulfillment_status', DB::raw('COUNT(*) as count'))
            ->groupBy('fulfillment_status')
            ->get()
            ->pluck('count', 'fulfillment_status')
            ->toArray();

        $byStatus = $orders->clone()
            ->select('status', DB::raw('COUNT(*) as count'))
            ->groupBy('status')
            ->get()
            ->pluck('count', 'status')
            ->toArray();

        return [
            'by_type' => $byType,
            'by_status' => $byStatus,
        ];
    }

    /**
     * Get peak hours
     */
    protected function getPeakHours(Carbon $start, Carbon $end): array
    {
        $isSqlite = DB::connection()->getDriverName() === 'sqlite';
        $hourExpr = $isSqlite
            ? DB::raw("CAST(strftime('%H', created_at) AS INTEGER) as hour")
            : DB::raw('HOUR(created_at) as hour');

        $data = Order::whereBetween('created_at', [$start, $end])
            ->select(
                $hourExpr,
                DB::raw('COUNT(*) as orders')
            )
            ->groupBy('hour')
            ->orderBy('hour')
            ->get();

        $hours = [];
        $orders = [];

        foreach ($data as $item) {
            $hours[] = sprintf('%02d:00', $item->hour);
            $orders[] = $item->orders;
        }

        return [
            'hours' => $hours,
            'orders' => $orders,
        ];
    }

    /**
     * Get payment methods breakdown
     */
    protected function getPaymentMethodsBreakdown(Carbon $start, Carbon $end): array
    {
        return Order::whereBetween('created_at', [$start, $end])
            ->where('payment_status', 'paid')
            ->select(
                'payment_method',
                DB::raw('COUNT(*) as count'),
                DB::raw('SUM(total) as revenue')
            )
            ->groupBy('payment_method')
            ->get()
            ->map(function ($item) {
                return [
                    'method' => $item->payment_method,
                    'count' => $item->count,
                    'revenue' => (float) $item->revenue,
                ];
            })
            ->toArray();
    }

    // ─── Advanced Reports ─────────────────────────────────────────────

    public function productPerformance(Request $request): JsonResponse
    {
        $limit = (int) $request->get('limit', 20);
        $period = $request->get('period', 'month');
        [$start, $end] = $this->getDateRange($period, $request->get('start_date'), $request->get('end_date'));

        $results = OrderItem::join('orders', 'order_items.order_id', '=', 'orders.id')
            ->leftJoin('refunds', function ($join) {
                $join->on('refunds.order_id', '=', 'orders.id')
                    ->whereColumn('refunds.order_id', 'orders.id');
            })
            ->whereBetween('orders.created_at', [$start, $end])
            ->where('orders.payment_status', 'paid')
            ->whereNotNull('order_items.product_id')
            ->select(
                'order_items.product_id',
                'order_items.name as product_name',
                DB::raw('SUM(order_items.quantity) as sold_qty'),
                DB::raw('SUM(order_items.quantity * order_items.price) as revenue'),
                DB::raw('AVG(order_items.price) as avg_price'),
                DB::raw('COUNT(DISTINCT refunds.id) as refund_count')
            )
            ->groupBy('order_items.product_id', 'order_items.name')
            ->orderByDesc('revenue')
            ->limit($limit)
            ->get()
            ->map(fn ($item) => [
                'product_id' => $item->product_id,
                'product_name' => $item->product_name,
                'sold_qty' => (int) $item->sold_qty,
                'revenue' => (float) $item->revenue,
                'avg_price' => round((float) $item->avg_price, 2),
                'return_rate' => $item->sold_qty > 0 ? round(($item->refund_count / $item->sold_qty) * 100, 2) : 0,
            ]);

        return response()->json($results);
    }

    public function profitAnalysis(Request $request): JsonResponse
    {
        $period = $request->get('period', 'month');
        [$start, $end] = $this->getDateRange($period, $request->get('start_date'), $request->get('end_date'));

        $results = OrderItem::join('orders', 'order_items.order_id', '=', 'orders.id')
            ->join('products', 'order_items.product_id', '=', 'products.id')
            ->leftJoin('categories', 'products.category_id', '=', 'categories.id')
            ->whereBetween('orders.created_at', [$start, $end])
            ->where('orders.payment_status', 'paid')
            ->whereNotNull('order_items.product_id')
            ->select(
                'categories.name as category_name',
                DB::raw('SUM(order_items.quantity * order_items.price) as revenue'),
                DB::raw('SUM(order_items.quantity * COALESCE(products.cost_price, 0)) as estimated_cost'),
                DB::raw('COUNT(DISTINCT orders.id) as order_count')
            )
            ->groupBy('categories.name')
            ->get()
            ->map(fn ($item) => [
                'category' => $item->category_name ?? 'Bez kategorii',
                'revenue' => (float) $item->revenue,
                'estimated_cost' => (float) $item->estimated_cost,
                'profit' => (float) $item->revenue - (float) $item->estimated_cost,
                'order_count' => (int) $item->order_count,
            ]);

        return response()->json($results);
    }

    public function retentionAnalysis(Request $request): JsonResponse
    {
        // Cohort: customers who placed first order in each month, and % who returned in 30/60/90 days
        $isSqlite = DB::connection()->getDriverName() === 'sqlite';
        $monthExpr = $isSqlite
            ? DB::raw("strftime('%Y-%m', MIN(orders.created_at)) as cohort_month")
            : DB::raw("DATE_FORMAT(MIN(orders.created_at), '%Y-%m') as cohort_month");

        $cohorts = Order::where('payment_status', 'paid')
            ->whereNotNull('customer_id')
            ->select('customer_id', $monthExpr)
            ->groupBy('customer_id')
            ->having(DB::raw('MIN(orders.created_at)'), '>=', now()->subYear())
            ->get()
            ->groupBy('cohort_month');

        $result = [];

        foreach ($cohorts as $month => $customers) {
            $customerIds = $customers->pluck('customer_id');
            $total = $customerIds->count();

            // One query for every order these customers placed, instead of
            // one MIN() query plus one more query PER customer (N+1) — group
            // in PHP and compare each customer's 1st vs 2nd order directly.
            $ordersByCustomer = Order::where('payment_status', 'paid')
                ->whereIn('customer_id', $customerIds)
                ->orderBy('created_at')
                ->get(['customer_id', 'created_at'])
                ->groupBy('customer_id');

            $r30 = $r60 = $r90 = 0;

            foreach ($ordersByCustomer as $customerOrders) {
                if ($customerOrders->count() < 2) {
                    continue;
                }

                $firstAt = Carbon::parse($customerOrders[0]->created_at);
                $returned = Carbon::parse($customerOrders[1]->created_at);
                $daysToReturn = $firstAt->diffInDays($returned);

                if ($daysToReturn <= 30) {
                    $r30++;
                }
                if ($daysToReturn <= 60) {
                    $r60++;
                }
                if ($daysToReturn <= 90) {
                    $r90++;
                }
            }

            $result[] = [
                'cohort' => $month,
                'total' => $total,
                'returned_30' => $total > 0 ? round($r30 / $total * 100, 1) : 0,
                'returned_60' => $total > 0 ? round($r60 / $total * 100, 1) : 0,
                'returned_90' => $total > 0 ? round($r90 / $total * 100, 1) : 0,
            ];
        }

        return response()->json($result);
    }

    public function customerAcquisition(Request $request): JsonResponse
    {
        $isSqlite = DB::connection()->getDriverName() === 'sqlite';
        $monthExpr = $isSqlite
            ? DB::raw("strftime('%Y-%m', orders.created_at) as month")
            : DB::raw("DATE_FORMAT(orders.created_at, '%Y-%m') as month");

        $orders = Order::where('payment_status', 'paid')
            ->whereNotNull('customer_id')
            ->select('customer_id', $monthExpr)
            ->whereBetween('created_at', [now()->subYear(), now()])
            ->get();

        // First order per customer
        $firstOrders = Order::where('payment_status', 'paid')
            ->whereNotNull('customer_id')
            ->select('customer_id', DB::raw('MIN(created_at) as first_at'))
            ->groupBy('customer_id')
            ->get()
            ->keyBy('customer_id');

        $byMonth = $orders->groupBy('month');
        $result = [];

        foreach ($byMonth as $month => $monthOrders) {
            $newCustomers = 0;
            $returningCustomers = 0;
            $seen = [];

            foreach ($monthOrders as $order) {
                if (in_array($order->customer_id, $seen)) {
                    continue;
                }
                $seen[] = $order->customer_id;

                $firstAt = isset($firstOrders[$order->customer_id])
                    ? Carbon::parse($firstOrders[$order->customer_id]->first_at)->format('Y-m')
                    : null;

                if ($firstAt === $month) {
                    $newCustomers++;
                } else {
                    $returningCustomers++;
                }
            }

            $result[] = [
                'month' => $month,
                'new' => $newCustomers,
                'returning' => $returningCustomers,
            ];
        }

        usort($result, fn ($a, $b) => strcmp($a['month'], $b['month']));

        return response()->json($result);
    }

    /**
     * Export report to CSV
     */
    public function exportCsv(Request $request)
    {
        $period = $request->get('period', 'week');
        $startDate = $request->get('start_date');
        $endDate = $request->get('end_date');

        [$start, $end] = $this->getDateRange($period, $startDate, $endDate);

        $orders = Order::with(['items', 'discountCode'])
            ->whereBetween('created_at', [$start, $end])
            ->where('payment_status', 'paid')
            ->orderBy('created_at', 'desc')
            ->get();

        $filename = 'raport_' . $start->format('Y-m-d') . '_' . $end->format('Y-m-d') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ];

        $callback = function () use ($orders) {
            $file = fopen('php://output', 'w');

            // BOM for Excel UTF-8 support
            fprintf($file, chr(0xEF) . chr(0xBB) . chr(0xBF));

            // Header
            fputcsv($file, [
                __('messages.report_order_number'),
                'Data',
                'Realizacja',
                'Klient',
                'Telefon',
                __('messages.report_items_total'),
                'Dostawa',
                'Rabat',
                'Razem',
                __('messages.report_payment_method'),
                'Status',
            ], ';');

            // Data
            foreach ($orders as $order) {
                fputcsv($file, [
                    $order->order_number,
                    $order->created_at->format('Y-m-d H:i:s'),
                    $order->fulfillment_status,
                    Csv::safe($order->customer_name),
                    Csv::safe($order->customer_phone),
                    number_format($order->subtotal, 2, ',', ''),
                    number_format($order->shipping_cost, 2, ',', ''),
                    number_format($order->discount, 2, ',', ''),
                    number_format($order->total, 2, ',', ''),
                    $order->payment_method,
                    $order->status,
                ], ';');
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
