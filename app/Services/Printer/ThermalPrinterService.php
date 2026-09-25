<?php

namespace App\Services\Printer;

use App\Models\Tenant\Order;
use App\Models\Tenant\Setting;
use Illuminate\Support\Facades\Log;

class ThermalPrinterService
{
    /**
     * Print an order receipt to the configured thermal printer.
     *
     * Returns true if printing was attempted, false if disabled or not configured.
     */
    public function printOrderReceipt(Order $order): bool
    {
        if (!Setting::get('thermal_printer_enabled', false)) {
            return false;
        }

        $printerIp = Setting::get('thermal_printer_ip');
        $printerPort = (int) Setting::get('thermal_printer_port', 9100);

        if (!$printerIp) {
            Log::warning('ThermalPrinterService: no printer IP configured');

            return false;
        }

        try {
            $receipt = $this->buildReceipt($order);
            $this->send($printerIp, $printerPort, $receipt);

            return true;
        } catch (\Throwable $e) {
            Log::error('ThermalPrinterService: send failed', ['error' => $e->getMessage()]);

            return false;
        }
    }

    protected function buildReceipt(Order $order): string
    {
        $lines = [];
        $lines[] = str_pad('ZAMOWIENIE #' . $order->order_number, 32, ' ', STR_PAD_BOTH);
        $lines[] = str_repeat('-', 32);

        foreach ($order->items ?? [] as $item) {
            $qty = $item->quantity ?? 1;
            $name = mb_substr($item->name ?? '', 0, 20);
            $price = number_format(($item->price ?? 0) * $qty, 2, ',', ' ');
            $lines[] = sprintf('%-20s %8s', $name, $price . ' PLN');
        }

        $lines[] = str_repeat('-', 32);
        $lines[] = sprintf('%-20s %8s', 'RAZEM:', number_format($order->total ?? 0, 2, ',', ' ') . ' PLN');
        $lines[] = '';
        $lines[] = __('messages.receipt_thanks');
        $lines[] = "\x1B\x64\x04"; // ESC d 4 – feed 4 lines
        $lines[] = "\x1D\x56\x00"; // GS  V 0 – cut

        return implode("\n", $lines);
    }

    protected function send(string $ip, int $port, string $data): void
    {
        $socket = @fsockopen($ip, $port, $errno, $errstr, 3);
        if (!$socket) {
            throw new \RuntimeException("Cannot connect to printer $ip:$port – $errstr ($errno)");
        }
        fwrite($socket, $data);
        fclose($socket);
    }
}
