<?php

namespace App\Services;

use App\Models\Tenant\Customer;
use App\Models\Tenant\Order;
use App\Models\Tenant\Product;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

class OrderImportService
{
    public function import(UploadedFile $file): array
    {
        $imported = 0;
        $skipped = 0;
        $errors = [];

        $handle = fopen($file->getRealPath(), 'r');
        if (!$handle) {
            return ['imported' => 0, 'skipped' => 0, 'errors' => ['Cannot open file']];
        }

        // Read header row
        $header = fgetcsv($handle, 0, ',');
        if (!$header) {
            fclose($handle);

            return ['imported' => 0, 'skipped' => 0, 'errors' => ['Empty file or invalid CSV']];
        }

        $header = array_map('trim', $header);
        $required = ['order_number', 'customer_email', 'customer_name', 'product_sku', 'quantity', 'price'];
        $missing = array_diff($required, $header);

        if (!empty($missing)) {
            fclose($handle);

            return ['imported' => 0, 'skipped' => 0, 'errors' => ['Missing columns: ' . implode(', ', $missing)]];
        }

        $rowNum = 1;
        $maxRows = 20000;
        $validStatuses = ['pending', 'confirmed', 'paid', 'processing', 'shipped', 'delivered', 'cancelled', 'refunded'];
        $validPaymentStatuses = ['pending', 'awaiting_payment', 'paid', 'failed', 'refunded', 'partially_refunded'];

        while (($row = fgetcsv($handle, 0, ',')) !== false) {
            $rowNum++;

            if ($rowNum > $maxRows + 1) {
                $errors[] = "Import stopped at {$maxRows} rows — split large files and upload separately.";
                break;
            }

            if (count($row) < count($required)) {
                $errors[] = "Row {$rowNum}: insufficient columns";
                $skipped++;

                continue;
            }

            $data = array_combine($header, $row);

            // Skip if order number already exists
            if (Order::where('order_number', trim($data['order_number']))->exists()) {
                $skipped++;

                continue;
            }

            $price = (float) ($data['price'] ?? 0);
            $qty = (int) ($data['quantity'] ?? 1);

            if ($price < 0 || $qty <= 0) {
                $errors[] = "Row {$rowNum}: 'price' must be non-negative and 'quantity' must be a positive integer.";
                $skipped++;

                continue;
            }

            $status = in_array($data['status'] ?? 'pending', $validStatuses, true) ? $data['status'] : 'pending';
            $paymentStatus = in_array($data['payment_status'] ?? 'pending', $validPaymentStatuses, true) ? $data['payment_status'] : 'pending';

            try {
                DB::transaction(function () use ($data, $price, $qty, $status, $paymentStatus) {
                    // Find or create customer
                    $customer = Customer::where('email', trim($data['customer_email']))->first();

                    // Find product
                    $product = Product::where('sku', trim($data['product_sku']))->first();

                    $subtotal = $price * $qty;
                    $shippingCost = max(0, (float) ($data['shipping_cost'] ?? 0));
                    $total = $subtotal + $shippingCost;

                    $order = Order::create([
                        'order_number' => trim($data['order_number']),
                        'customer_id' => $customer?->id,
                        'customer_name' => trim($data['customer_name']),
                        'customer_email' => trim($data['customer_email']),
                        'status' => $status,
                        'payment_method' => $data['payment_method'] ?? 'import',
                        'payment_status' => $paymentStatus,
                        'subtotal' => $subtotal,
                        'shipping_cost' => $shippingCost,
                        'discount' => 0,
                        'tax' => 0,
                        'total' => $total,
                        'currency' => 'PLN',
                        'fulfillment_status' => 'unfulfilled',
                    ]);

                    $order->items()->create([
                        'product_id' => $product?->id,
                        'name' => $product?->name ?? trim($data['product_sku']),
                        'sku' => trim($data['product_sku']),
                        'price' => $price,
                        'quantity' => $qty,
                        'product_type' => $product?->type ?? 'physical',
                        'total' => $subtotal,
                        'tax_rate' => 0,
                    ]);
                });

                $imported++;
            } catch (\Throwable $e) {
                $errors[] = "Row {$rowNum}: " . $e->getMessage();
                $skipped++;
            }
        }

        fclose($handle);

        return compact('imported', 'skipped', 'errors');
    }
}
