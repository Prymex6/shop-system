<?php

namespace App\Services;

use App\Models\Tenant\Category;
use App\Models\Tenant\Product;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

class ProductImportService
{
    /**
     * Import products from a CSV file.
     *
     * Expected columns: name, slug, price, category_id, category_name, description,
     *                   sku, type, stock_quantity, track_stock
     *
     * @return array{imported: int, skipped: int, errors: array}
     */
    public function import(UploadedFile $file): array
    {
        $imported = 0;
        $skipped = 0;
        $errors = [];

        $handle = fopen($file->getRealPath(), 'r');

        if ($handle === false) {
            return ['imported' => 0, 'skipped' => 0, 'errors' => ['Could not open file.']];
        }

        // Read header row
        $headers = fgetcsv($handle, 0, ',');

        if (!$headers) {
            fclose($handle);

            return ['imported' => 0, 'skipped' => 0, 'errors' => ['CSV file is empty or missing headers.']];
        }

        $headers = array_map(fn ($h) => strtolower(trim($h)), $headers);

        $row = 1;
        $maxRows = 20000;

        while (($rawRow = fgetcsv($handle, 0, ',')) !== false) {
            $row++;

            if ($row > $maxRows + 1) {
                $errors[] = "Import stopped at {$maxRows} rows — split large files and upload separately.";
                break;
            }

            if (count($rawRow) !== count($headers)) {
                $errors[] = "Row {$row}: column count mismatch.";
                $skipped++;

                continue;
            }

            $data = array_combine($headers, $rawRow);
            $data = array_map('trim', $data);

            // Validate required fields
            if (empty($data['name'])) {
                $errors[] = "Row {$row}: 'name' is required.";
                $skipped++;

                continue;
            }

            if (!isset($data['price']) || !is_numeric($data['price']) || (float) $data['price'] < 0) {
                $errors[] = "Row {$row}: 'price' must be a non-negative number.";
                $skipped++;

                continue;
            }

            // Resolve category
            $categoryId = null;
            if (!empty($data['category_id']) && is_numeric($data['category_id'])) {
                $categoryId = (int) $data['category_id'];
            } elseif (!empty($data['category_name'])) {
                $category = Category::firstOrCreate(
                    ['name' => $data['category_name']],
                    ['slug' => Str::slug($data['category_name']), 'is_active' => true]
                );
                $categoryId = $category->id;
            }

            // Generate slug
            $slug = !empty($data['slug']) ? Str::slug($data['slug']) : Str::slug($data['name']);
            $sku = !empty($data['sku']) ? $data['sku'] : null;

            // Deduplication: skip if slug or SKU already exists
            if (Product::where('slug', $slug)->exists()) {
                $errors[] = "Row {$row}: product with slug '{$slug}' already exists — skipped.";
                $skipped++;

                continue;
            }

            if ($sku && Product::where('sku', $sku)->exists()) {
                $errors[] = "Row {$row}: product with SKU '{$sku}' already exists — skipped.";
                $skipped++;

                continue;
            }

            $trackStock = filter_var($data['track_stock'] ?? false, FILTER_VALIDATE_BOOLEAN);
            $stockQuantity = $trackStock ? ((int) ($data['stock_quantity'] ?? 0)) : null;
            $type = in_array($data['type'] ?? 'physical', ['physical', 'digital']) ? ($data['type'] ?? 'physical') : 'physical';

            try {
                Product::create([
                    'name' => $data['name'],
                    'slug' => $slug,
                    'price' => (float) $data['price'],
                    'category_id' => $categoryId,
                    'description' => $data['description'] ?? null,
                    'sku' => $sku,
                    'type' => $type,
                    'stock_quantity' => $stockQuantity,
                    'track_stock' => $trackStock,
                    // Default to unpublished — a bad/malformed row (see the price
                    // check above) shouldn't be able to go live on the storefront
                    // without a manager reviewing it first. Honored only if the
                    // CSV explicitly sets the column.
                    'is_published' => filter_var($data['is_published'] ?? false, FILTER_VALIDATE_BOOLEAN),
                ]);

                $imported++;
            } catch (\Throwable $e) {
                $errors[] = "Row {$row}: " . $e->getMessage();
                $skipped++;
            }
        }

        fclose($handle);

        return [
            'imported' => $imported,
            'skipped' => $skipped,
            'errors' => $errors,
        ];
    }
}
