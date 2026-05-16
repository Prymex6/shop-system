<?php

namespace App\Http\Controllers\Tenant\Manager;

use App\Http\Controllers\Controller;
use App\Models\Tenant\Customer;
use App\Services\AuditService;
use App\Support\Csv;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class CustomerController extends Controller
{
    public function index(Request $request)
    {
        $query = Customer::withCount('orders')->latest();

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        $customers = $query->paginate(20)->withQueryString();

        return Inertia::render('Tenant/Manager/Customers/Index', [
            'customers' => $customers,
            'filters' => $request->only(['search']),
        ]);
    }

    public function show(Customer $customer)
    {
        // GDPR export downloads already set the precedent of logging PII
        // access (gdpr.export_downloaded) — a manager viewing a customer's
        // full profile/order history is the same category of access and
        // was previously invisible to the Audit Log panel entirely.
        AuditService::log('customer.viewed', $customer);

        $customer->load(['orders' => function ($q) {
            $q->with('items')->latest()->limit(20);
        }]);

        $totalSpent = $customer->orders()
            ->where(function ($q) {
                $q->where('payment_status', 'paid')
                    ->orWhere(function ($inner) {
                        $inner->where('payment_method', 'cash_on_delivery')
                            ->where('status', 'completed');
                    });
            })
            ->sum('total');

        $favoriteProducts = DB::table('order_items')
            ->select('name', DB::raw('SUM(quantity) as total_qty'))
            ->whereIn('order_id', $customer->orders()->select('id'))
            ->groupBy('name')
            ->orderByDesc('total_qty')
            ->limit(5)
            ->get();

        return Inertia::render('Tenant/Manager/Customers/Show', [
            'customer' => $customer,
            'totalSpent' => (float) $totalSpent,
            'favoriteProducts' => $favoriteProducts,
        ]);
    }

    public function export(Request $request)
    {
        // cursor() instead of get() — this table only grows, and building the
        // whole CSV in memory from a fully-materialized collection doesn't
        // scale with customer count the way a cursor does.
        $customers = Customer::select('id', 'name', 'email', 'phone', 'delivery_city', 'loyalty_points', 'loyalty_tier', 'created_at')
            ->withCount('orders')
            ->cursor();

        $csv = "ID,Imię i nazwisko,E-mail,Telefon,Miasto,Punkty lojalnościowe,Poziom,Liczba zamówień,Data rejestracji\n";
        foreach ($customers as $c) {
            $csv .= implode(',', [
                $c->id,
                Csv::field($c->name),
                Csv::field($c->email),
                Csv::field($c->phone ?? ''),
                Csv::field($c->delivery_city ?? ''),
                $c->loyalty_points ?? 0,
                $c->loyalty_tier ?? 'bronze',
                $c->orders_count,
                $c->created_at->format('Y-m-d'),
            ]) . "\n";
        }

        return response($csv, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="klienci-' . now()->format('Y-m-d') . '.csv"',
        ]);
    }
}
