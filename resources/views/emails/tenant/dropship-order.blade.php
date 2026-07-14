<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <title>Zamówienie dropship #{{ $order->order_number }}</title>
    <style>
        body { font-family: Arial, sans-serif; color: #333; line-height: 1.6; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        h2 { color: #1e3a5f; }
        table { width: 100%; border-collapse: collapse; margin-top: 16px; }
        th { background: #1e3a5f; color: #fff; padding: 8px 12px; text-align: left; }
        td { padding: 8px 12px; border-bottom: 1px solid #e5e7eb; }
        .total { font-weight: bold; font-size: 1.1em; margin-top: 12px; }
        .address { background: #f9fafb; padding: 12px; border-radius: 4px; margin-top: 12px; }
        .footer { margin-top: 24px; font-size: 0.85em; color: #666; }
    </style>
</head>
<body>
<div class="container">
    <h2>Zamówienie dropship #{{ $order->order_number }}</h2>
    <p>Dzień dobry {{ $supplier->name }},</p>
    <p>Prosimy o realizację poniższego zamówienia dropshippingowego (PO #{{ $purchaseOrder->id }}).</p>

    <table>
        <thead>
            <tr>
                <th>Produkt</th>
                <th>Ilość</th>
                <th>Cena jedn.</th>
                <th>Razem</th>
            </tr>
        </thead>
        <tbody>
            @foreach($purchaseOrder->items as $item)
            <tr>
                <td>{{ $item->product->name ?? 'N/A' }}@if($item->variant) ({{ implode(', ', $item->variant->attributes ?? []) }})@endif</td>
                <td>{{ $item->quantity }}</td>
                <td>{{ number_format($item->unit_cost, 2, ',', ' ') }} PLN</td>
                <td>{{ number_format($item->total_cost, 2, ',', ' ') }} PLN</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <p class="total">Łącznie: {{ number_format($purchaseOrder->total, 2, ',', ' ') }} PLN</p>

    <div class="address">
        <strong>Adres dostawy do klienta:</strong><br>
        {{ $order->customer_name }}<br>
        @php $addr = is_array($order->shipping_address) ? $order->shipping_address : json_decode($order->shipping_address, true); @endphp
        {{ $addr['street'] ?? '' }}<br>
        {{ $addr['postcode'] ?? '' }} {{ $addr['city'] ?? '' }}<br>
        {{ $addr['country'] ?? '' }}<br>
        Tel: {{ $order->customer_phone }}
    </div>

    <div class="footer">
        <p>To jest automatyczna wiadomość wygenerowana przez system sprzedaży. W razie pytań prosimy o kontakt.</p>
    </div>
</div>
</body>
</html>
