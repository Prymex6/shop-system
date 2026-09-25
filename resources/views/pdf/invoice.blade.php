<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('mail.invoice_title') }} {{ $order->invoice_number ?? $order->order_number }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; font-size: 14px; color: #333; background: #fff; }
        .page { max-width: 800px; margin: 0 auto; padding: 40px; }
        .header { display: flex; justify-content: space-between; margin-bottom: 40px; padding-bottom: 20px; border-bottom: 2px solid #e5e7eb; }
        .company-name { font-size: 22px; font-weight: bold; color: #1e40af; }
        .invoice-title { font-size: 28px; font-weight: bold; color: #374151; text-align: right; }
        .invoice-number { font-size: 14px; color: #6b7280; }
        .addresses { display: grid; grid-template-columns: 1fr 1fr; gap: 40px; margin-bottom: 30px; }
        .address-block h3 { font-size: 11px; text-transform: uppercase; color: #9ca3af; letter-spacing: 0.05em; margin-bottom: 8px; }
        .address-block p { line-height: 1.6; }
        .items-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        .items-table th { background: #f9fafb; padding: 10px 12px; text-align: left; font-size: 12px; text-transform: uppercase; color: #6b7280; border-bottom: 1px solid #e5e7eb; }
        .items-table td { padding: 10px 12px; border-bottom: 1px solid #f3f4f6; }
        .items-table .text-right { text-align: right; }
        .totals { float: right; width: 280px; }
        .totals table { width: 100%; }
        .totals td { padding: 6px 0; }
        .totals .total-row td { font-size: 16px; font-weight: bold; border-top: 2px solid #374151; padding-top: 10px; margin-top: 5px; }
        .totals .label { color: #6b7280; }
        .totals .amount { text-align: right; }
        .meta { clear: both; margin-top: 40px; padding-top: 20px; border-top: 1px solid #e5e7eb; font-size: 12px; color: #9ca3af; }
        .badge { display: inline-block; padding: 4px 12px; border-radius: 9999px; font-size: 12px; font-weight: 600; }
        .badge-paid { background: #d1fae5; color: #065f46; }
        .badge-unpaid { background: #fee2e2; color: #991b1b; }
        .print-btn { position: fixed; top: 20px; right: 20px; padding: 10px 20px; background: #1e40af; color: white; border: none; border-radius: 8px; cursor: pointer; font-size: 14px; font-weight: 600; }
        @media print {
            .print-btn { display: none; }
            .no-print { display: none !important; }
            .page { padding: 0; }
        }
    </style>
</head>
<body>

<button class="print-btn" onclick="window.print()">Drukuj / Zapisz PDF</button>

<div class="page">
    <!-- Header -->
    <div class="header">
        <div>
            <div class="company-name">{{ $settings['shop_name'] ?? 'Sklep' }}</div>
            @if(!empty($settings['shop_address']))
                <p style="color: #6b7280; margin-top: 4px;">{{ $settings['shop_address'] }}</p>
            @endif
            @if(!empty($settings['shop_phone']))
                <p style="color: #6b7280;">Tel: {{ $settings['shop_phone'] }}</p>
            @endif
            @if(!empty($settings['shop_email']))
                <p style="color: #6b7280;">{{ $settings['shop_email'] }}</p>
            @endif
            @if(!empty($settings['shop_nip']))
                <p style="color: #6b7280; margin-top: 4px;">NIP: {{ $settings['shop_nip'] }}</p>
            @endif
        </div>
        <div>
            <div class="invoice-title">{{ __('mail.invoice_title') }}</div>
            <div class="invoice-number" style="margin-top: 8px; font-weight: 600; color: #374151;">Nr: {{ $order->invoice_number ?? $order->order_number }}</div>
            <div class="invoice-number">{{ __('mail.invoice_issued', ['date' => $order->created_at->format('d.m.Y')]) }}</div>
            <div class="invoice-number">{{ __('mail.invoice_sold', ['date' => $order->created_at->format('d.m.Y')]) }}</div>
            <div style="margin-top: 8px;">
                <span class="{{ $order->payment_status === 'paid' ? 'badge badge-paid' : 'badge badge-unpaid' }}">
                    {{ $order->payment_status === 'paid' ? __('mail.invoice_paid_stamp') : __('mail.invoice_unpaid_stamp') }}
                </span>
            </div>
        </div>
    </div>

    <!-- Addresses -->
    <div class="addresses">
        <div class="address-block">
            <h3>{{ __('mail.invoice_seller') }}</h3>
            <p>
                <strong>{{ $settings['shop_name'] ?? 'Sklep' }}</strong><br>
                @if(!empty($settings['shop_address'])){{ $settings['shop_address'] }}<br>@endif
                @if(!empty($settings['shop_nip']))NIP: {{ $settings['shop_nip'] }}<br>@endif
                @if(!empty($settings['shop_phone']))Tel: {{ $settings['shop_phone'] }}<br>@endif
            </p>
        </div>
        <div class="address-block">
            <h3>{{ __('mail.invoice_buyer') }}</h3>
            <p>
                <strong>{{ $order->customer_name ?? 'Klient' }}</strong><br>
                @php $shipAddr = collect([$order->shipping_address['street'] ?? null, trim(($order->shipping_address['postcode'] ?? '') . ' ' . ($order->shipping_address['city'] ?? '')), $order->shipping_address['country'] ?? null])->filter()->implode(', '); @endphp
                @if($shipAddr !== '')
                    {{ $shipAddr }}<br>
                @endif
                @if($order->customer_phone)Tel: {{ $order->customer_phone }}<br>@endif
                @if($order->customer_email){{ $order->customer_email }}<br>@endif
                @if($order->buyer_nip)<strong>NIP: {{ $order->buyer_nip }}</strong><br>@endif
            </p>
            @if(!$order->buyer_nip)
            <form id="nipForm" style="margin-top: 10px; display: flex; gap: 6px;" class="no-print">
                <input type="text" id="nipInput" placeholder="NIP nabywcy (opcjonalnie)" maxlength="15"
                    style="border: 1px solid #d1d5db; border-radius: 4px; padding: 4px 8px; font-size: 12px; flex: 1;">
                <button type="button" onclick="addNip()"
                    style="padding: 4px 12px; background: #1e40af; color: white; border: none; border-radius: 4px; font-size: 12px; cursor: pointer;">Dodaj</button>
            </form>
            @endif
        </div>
    </div>

    <!-- Order items -->
    <table class="items-table">
        <thead>
            <tr>
                <th>{{ __('mail.invoice_item') }}</th>
                <th class="text-right">{{ __('mail.invoice_quantity') }}</th>
                <th class="text-right">{{ __('mail.invoice_unit_price') }}</th>
                <th class="text-right">{{ __('mail.invoice_line_total') }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach($order->items as $item)
            <tr>
                <td>
                    {{ $item->name }}
                    @if($item->variant_label ?? $item->variant_name) <span style="color: #9ca3af; font-size: 12px;">({{ $item->variant_label ?? $item->variant_name }})</span>@endif
                </td>
                <td class="text-right">{{ $item->quantity }}</td>
                <td class="text-right">@money($item->price, $order->currency ?? 'PLN')</td>
                <td class="text-right">@money($item->price * $item->quantity, $order->currency ?? 'PLN')</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <!-- VAT breakdown -->
    <div style="margin-bottom: 20px; clear: both;">
        <h4 style="font-size: 11px; text-transform: uppercase; color: #9ca3af; letter-spacing: 0.05em; margin-bottom: 6px;">{{ __('mail.invoice_vat_summary') }}</h4>
        <table style="width: 100%; border-collapse: collapse; font-size: 13px;">
            <thead>
                <tr style="background: #f9fafb;">
                    <th style="padding: 6px 10px; text-align: left; border: 1px solid #e5e7eb; color: #6b7280; font-size: 11px; text-transform: uppercase;">Stawka</th>
                    <th style="padding: 6px 10px; text-align: right; border: 1px solid #e5e7eb; color: #6b7280; font-size: 11px; text-transform: uppercase;">{{ __('mail.invoice_net') }}</th>
                    <th style="padding: 6px 10px; text-align: right; border: 1px solid #e5e7eb; color: #6b7280; font-size: 11px; text-transform: uppercase;">VAT</th>
                    <th style="padding: 6px 10px; text-align: right; border: 1px solid #e5e7eb; color: #6b7280; font-size: 11px; text-transform: uppercase;">{{ __('mail.invoice_gross') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach($vatGroups as $vg)
                @if($vg['gross'] > 0)
                <tr>
                    <td style="padding: 6px 10px; border: 1px solid #e5e7eb;">{{ $vg['rate'] }}</td>
                    <td style="padding: 6px 10px; border: 1px solid #e5e7eb; text-align: right;">@money($vg['net'], $order->currency ?? 'PLN')</td>
                    <td style="padding: 6px 10px; border: 1px solid #e5e7eb; text-align: right;">@money($vg['vat'], $order->currency ?? 'PLN')</td>
                    <td style="padding: 6px 10px; border: 1px solid #e5e7eb; text-align: right;">@money($vg['gross'], $order->currency ?? 'PLN')</td>
                </tr>
                @endif
                @endforeach
            </tbody>
        </table>
    </div>

    <!-- Totals -->
    <div class="totals">
        <table>
            <tr>
                <td class="label">{{ __('mail.invoice_subtotal') }}</td>
                <td class="amount">@money($order->subtotal, $order->currency ?? 'PLN')</td>
            </tr>
            @if($order->shipping_cost > 0)
            <tr>
                <td class="label">{{ __('mail.invoice_delivery') }}</td>
                <td class="amount">@money($order->shipping_cost, $order->currency ?? 'PLN')</td>
            </tr>
            @endif
            @if($order->discount > 0)
            <tr>
                <td class="label">{{ __('mail.invoice_discount') }}</td>
                <td class="amount" style="color: #059669;">-@money($order->discount, $order->currency ?? 'PLN')</td>
            </tr>
            @endif
            <tr class="total-row">
                <td class="label"><strong>{{ __('mail.invoice_total') }}</strong></td>
                <td class="amount"><strong>@money($order->total, $order->currency ?? 'PLN')</strong></td>
            </tr>
        </table>
    </div>

    <!-- Meta -->
    <div class="meta">
        @if($order->shippingMethod)<p>{{ __('mail.invoice_shipping_method', ['method' => $order->shippingMethod->name]) }}</p>@endif
        <p>{{ __('mail.invoice_payment_method', ['method' => ['przelewy24' => 'Przelewy24', 'payu' => 'PayU', 'tpay' => 'Tpay', 'stripe' => 'Stripe', 'bank_transfer' => __('mail.payment_bank_transfer'), 'cash_on_delivery' => __('mail.payment_on_delivery')][$order->payment_method] ?? $order->payment_method]) }}</p>
        @if($order->paid_at)<p>{{ __('mail.invoice_paid_at', ['when' => $order->paid_at->format('d.m.Y H:i')]) }}</p>@endif
        <p style="margin-top: 12px; font-size: 11px;">{{ __('mail.invoice_footer', ['app' => config('app.name')]) }}</p>
    </div>
</div>

<script>
function addNip() {
    const nip = document.getElementById('nipInput').value.trim();
    if (!nip) return;
    const url = new URL(window.location.href);
    url.searchParams.set('nip', nip);
    window.location.href = url.toString();
}
</script>
</body>
</html>
