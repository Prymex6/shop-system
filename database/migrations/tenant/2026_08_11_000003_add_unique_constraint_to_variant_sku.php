<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * products.sku is unique, but product_variants.sku never was — a manager
 * could create two variants (same or different products) with an
 * identical SKU, a real risk for barcode scanning, warehouse picking, and
 * any future SKU-keyed integration (one scan could match two different
 * physical products in the system).
 */
return new class extends Migration
{
    public function up(): void
    {
        // Defensive: if this tenant somehow already has duplicate SKUs,
        // adding a unique index would fail outright and block
        // `tenants:migrate --force` for every other tenant in the same run.
        // Skip (and log) rather than crash — a tenant with pre-existing
        // duplicates needs manual cleanup before the constraint can apply.
        $duplicates = DB::table('product_variants')
            ->whereNotNull('sku')
            ->select('sku')
            ->groupBy('sku')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        if ($duplicates->isNotEmpty()) {
            Log::warning('add_unique_constraint_to_variant_sku: skipped — existing duplicate SKUs found', [
                'duplicate_skus' => $duplicates->pluck('sku')->all(),
            ]);

            return;
        }

        Schema::table('product_variants', function (Blueprint $table) {
            $table->unique('sku');
        });
    }

    public function down(): void
    {
        try {
            Schema::table('product_variants', function (Blueprint $table) {
                $table->dropUnique(['sku']);
            });
        } catch (Throwable $e) {
            // up() may have skipped creating it (pre-existing duplicates) — nothing to drop.
        }
    }
};
