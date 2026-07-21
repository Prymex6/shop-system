<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->integer('min_order_qty')->nullable()->after('allow_backorder');
            $table->integer('max_order_qty')->nullable()->after('min_order_qty');
            $table->integer('reorder_point')->nullable()->after('low_stock_threshold');
            $table->integer('reorder_quantity')->nullable()->after('reorder_point');
            // supplier_id FK added after suppliers table in migration 200009
            $table->unsignedBigInteger('supplier_id')->nullable()->after('category_id');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropForeign(['supplier_id']);
            $table->dropColumn(['min_order_qty', 'max_order_qty', 'reorder_point', 'reorder_quantity', 'supplier_id']);
        });
    }
};
