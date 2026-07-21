<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * ProductBundle/ProductBundleItem were fully configurable in the manager
 * panel but completely unreachable from the storefront — cart, checkout,
 * and order_items only ever dealt with plain products. A bundle purchase
 * decomposes into one real order_item per bundle component (so stock
 * locking, tax calculation, and digital delivery all reuse the existing
 * per-product logic unmodified) — these columns tag which bundle a
 * component order_item came from, purely for display/grouping.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->foreignId('bundle_id')->nullable()->after('variant_id')->constrained('product_bundles')->nullOnDelete();
            $table->string('bundle_name')->nullable()->after('bundle_id');
        });
    }

    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropConstrainedForeignId('bundle_id');
            $table->dropColumn('bundle_name');
        });
    }
};
