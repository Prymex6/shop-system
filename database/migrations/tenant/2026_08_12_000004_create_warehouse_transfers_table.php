<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The only existing way to move stock between warehouses was two
 * independent adjustStock() calls (negative delta on the source, positive
 * on the destination) — two separate DB transactions with no shared
 * atomicity, and adjustStock() silently floors an over-large deduction at
 * zero instead of rejecting it, so a "transfer" of more than the source
 * warehouse actually holds created stock out of nowhere. This table is the
 * audit trail for the new atomic MultiWarehouseService::transferStock().
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('warehouse_transfers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignId('variant_id')->nullable()->constrained('product_variants')->nullOnDelete();
            $table->foreignId('from_warehouse_id')->constrained('warehouses')->cascadeOnDelete();
            $table->foreignId('to_warehouse_id')->constrained('warehouses')->cascadeOnDelete();
            $table->unsignedInteger('quantity');
            $table->string('reason')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            $table->index(['product_id', 'variant_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('warehouse_transfers');
    }
};
