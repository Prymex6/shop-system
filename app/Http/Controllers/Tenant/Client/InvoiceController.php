<?php

namespace App\Http\Controllers\Tenant\Client;

use App\Http\Controllers\Controller;
use App\Models\Tenant\Order;
use App\Models\Tenant\Setting;
use App\Models\Tenant\TaxRate;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class InvoiceController extends Controller
{
    /**
     * Manager-accessible invoice (test version only).
     * Route: GET /manager/orders/{orderNumber}/faktura
     */
    public function showForManager(Request $request, string $orderNumber)
    {
        if ((tenancy()->tenant?->version ?? 'stable') !== 'test') {
            abort(403, __('messages.invoices_demo_only'));
        }

        $order = Order::with('items')
            ->where('order_number', $orderNumber)
            ->firstOrFail();

        return $this->renderInvoice($request, $order);
    }

    /**
     * Customer-accessible invoice.
     * Route: GET /zamowienia/{orderNumber}/faktura (auth.customer)
     */
    public function show(Request $request, string $orderNumber)
    {
        $customer = Auth::guard('customer')->user();

        abort_if(!$customer, 403);

        $order = Order::with('items')
            ->where('order_number', $orderNumber)
            ->where('customer_id', $customer->id)
            ->firstOrFail();

        return $this->renderInvoice($request, $order);
    }

    private function renderInvoice(Request $request, Order $order): View
    {
        // Generate FV number if not already assigned
        if (!$order->invoice_number) {
            $invoiceNumber = DB::transaction(function () use ($order) {
                $year = $order->created_at->format('Y');
                $prefix = 'FV/' . $year . '/';
                $last = DB::table('orders')
                    ->where('invoice_number', 'like', $prefix . '%')
                    ->lockForUpdate()
                    ->max('invoice_number');
                $seq = $last ? ((int) substr($last, strlen($prefix))) + 1 : 1;

                return $prefix . str_pad($seq, 3, '0', STR_PAD_LEFT);
            });
            $order->update(['invoice_number' => $invoiceNumber]);
        }

        // Handle optional buyer NIP from query param
        $buyerNip = $request->query('nip');
        if ($buyerNip && !$order->buyer_nip) {
            $order->update(['buyer_nip' => preg_replace('/[^0-9\-]/', '', $buyerNip)]);
            $order->refresh();
        }

        $settings = Setting::getAllAsArray();

        return view('pdf.invoice', [
            'order' => $order,
            'settings' => $settings,
            'vatGroups' => $this->computeVat($order),
        ]);
    }

    /**
     * Groups by each item's *actual* configured tax rate instead of assuming
     * every product is 8% and shipping is 23% — a product genuinely taxed at
     * the standard 23% rate was previously shown on the legal VAT invoice as
     * if it were taxed at the reduced 8% rate.
     */
    private function computeVat(Order $order): array
    {
        $groups = [];

        $addToGroup = function (float $rate, float $gross) use (&$groups) {
            $net = $rate > 0 ? round($gross / (1 + $rate / 100), 2) : $gross;
            $vat = round($gross - $net, 2);

            $key = (string) $rate;
            if (!isset($groups[$key])) {
                $groups[$key] = ['rate' => rtrim(rtrim(number_format($rate, 2, '.', ''), '0'), '.') . '%', 'net' => 0, 'vat' => 0, 'gross' => 0];
            }
            $groups[$key]['net'] += $net;
            $groups[$key]['vat'] += $vat;
            $groups[$key]['gross'] += $gross;
        };

        foreach ($order->items as $item) {
            $addToGroup((float) $item->tax_rate, round($item->price * $item->quantity, 2));
        }

        if ($order->shipping_cost > 0) {
            $defaultRate = TaxRate::getDefault()?->rate ?? 23;
            $addToGroup((float) $defaultRate, round((float) $order->shipping_cost, 2));
        }

        return array_values($groups);
    }
}
