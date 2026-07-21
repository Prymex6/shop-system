<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained('products')->nullOnDelete();
            $table->foreignId('variant_id')->nullable()->constrained('product_variants')->nullOnDelete();

            // Snapshot at time of order
            $table->string('name')->comment('Product name at time of order');
            $table->string('sku', 100)->nullable();
            $table->string('variant_label')->nullable()->comment('e.g. "Kolor: Czerwony, Rozmiar: XL"');
            $table->enum('product_type', ['physical', 'digital'])->default('physical');
            $table->decimal('price', 10, 2)->comment('Unit price at time of order');
            $table->decimal('tax_rate', 5, 2)->default(0)->comment('VAT % applied');
            $table->integer('quantity');
            $table->decimal('total', 10, 2);

            $table->timestamps();

            $table->index('order_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_items');
    }
};
