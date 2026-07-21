<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('warehouses', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('city');
            $table->string('address');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('product_warehouse_stock', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignId('variant_id')->nullable()->constrained('product_variants')->cascadeOnDelete();
            $table->foreignId('warehouse_id')->constrained('warehouses')->cascadeOnDelete();
            $table->integer('quantity')->default(0);
            $table->integer('reserved_quantity')->default(0);
            $table->timestamps();
            $table->unique(['product_id', 'variant_id', 'warehouse_id'], 'pws_product_variant_warehouse_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_warehouse_stock');
        Schema::dropIfExists('warehouses');
    }
};
