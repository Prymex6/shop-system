<?php

namespace App\Services;

use Illuminate\Support\Collection;

class PickingListService
{
    /**
     * Generate a picking list from a collection of orders.
     * Returns: [ product_id => [ name, sku, total_qty, locations[] ] ]
     */
    public function generate(Collection $orders): array
    {
        $list = [];

        foreach ($orders as $order) {
            foreach ($order->items as $item) {
                $key = $item->product_id . '_' . ($item->variant_id ?? 'base');

                if (!isset($list[$key])) {
                    $list[$key] = [
                        'product_id' => $item->product_id,
                        'variant_id' => $item->variant_id,
                        'name' => $item->product_name ?? ($item->product->name ?? 'N/A'),
                        'sku' => $item->sku ?? ($item->variant->sku ?? $item->product->sku ?? ''),
                        'image' => $item->product->image ?? null,
                        'total_qty' => 0,
                        'order_refs' => [],
                    ];
                }

                $list[$key]['total_qty'] += $item->quantity;
                $list[$key]['order_refs'][] = $order->order_number;
            }
        }

        // Sort by name
        usort($list, fn ($a, $b) => strcmp($a['name'], $b['name']));

        return array_values($list);
    }

    public function exportPdf(Collection $orders): string
    {
        $items = $this->generate($orders);
        $orderNums = $orders->pluck('order_number')->implode(', ');
        $date = now()->format('d.m.Y H:i');

        // The sheet is printed and carried around the warehouse, so it is
        // written in whatever language the people working there use.
        $ordersLabel = __('messages.picking_orders');
        $generatedLabel = __('messages.picking_generated');
        $quantityLabel = __('messages.picking_quantity');

        $rows = '';
        foreach ($items as $i => $item) {
            $orderRefs = implode(', ', array_unique($item['order_refs']));
            $rows .= '<tr>
                <td>' . ($i + 1) . '</td>
                <td><strong>' . e($item['name']) . "</strong><br><small style='color:#666'>" . e($item['sku']) . "</small></td>
                <td style='text-align:center;font-size:1.5em;font-weight:bold;color:#2563eb'>" . $item['total_qty'] . "</td>
                <td style='font-size:0.8em;color:#555'>" . e($orderRefs) . "</td>
                <td style='width:80px'>&nbsp;</td>
            </tr>";
        }

        return <<<HTML
        <!DOCTYPE html>
        <html lang="pl">
        <head>
            <meta charset="UTF-8">
            <title>Lista kompletacji</title>
            <style>
                * { margin:0; padding:0; box-sizing:border-box; }
                body { font-family: Arial, sans-serif; font-size:14px; padding:20px; }
                h1 { font-size:20px; margin-bottom:4px; }
                .meta { color:#666; font-size:12px; margin-bottom:16px; }
                table { width:100%; border-collapse:collapse; }
                th { background:#1e3a5f; color:#fff; padding:8px 10px; text-align:left; }
                td { padding:8px 10px; border-bottom:1px solid #e5e7eb; vertical-align:middle; }
                tr:nth-child(even) td { background:#f9fafb; }
                @media print {
                    button { display:none; }
                }
            </style>
        </head>
        <body>
            <h1>Lista kompletacji</h1>
            <p class="meta">{$ordersLabel}: {$orderNums} &nbsp;|&nbsp; {$generatedLabel}: {$date}</p>
            <button onclick="window.print()" style="margin-bottom:12px;padding:6px 14px;background:#2563eb;color:#fff;border:none;border-radius:4px;cursor:pointer">Drukuj</button>
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Produkt</th>
                        <th>{$quantityLabel}</th>
                        <th>{$ordersLabel}</th>
                        <th>Pobrane ✓</th>
                    </tr>
                </thead>
                <tbody>{$rows}</tbody>
            </table>
        </body>
        </html>
        HTML;
    }
}
