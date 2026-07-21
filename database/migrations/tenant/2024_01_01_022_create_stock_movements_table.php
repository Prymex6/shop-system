<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignId('variant_id')->nullable()->constrained('product_variants')->nullOnDelete();
            $table->integer('quantity_change')->comment('Positive = stock added, negative = stock removed');
            $table->integer('quantity_after')->comment('Stock level after this movement');
            $table->enum('type', ['sale', 'refund', 'adjustment', 'import', 'damage', 'return'])->index();
            $table->string('reason', 300)->nullable();
            $table->unsignedBigInteger('reference_id')->nullable()->comment('Order ID or adjustment ID');
            $table->string('reference_type', 50)->nullable()->comment('order, adjustment, etc.');
            $table->timestamps();

            $table->index(['product_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_movements');
    }
};
