<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('wishlists', function (Blueprint $table) {
            $table->decimal('price_at_added', 10, 2)->nullable()->after('product_id');
            $table->boolean('price_drop_notified')->default(false)->after('price_at_added');
            $table->boolean('was_out_of_stock')->default(false)->after('price_drop_notified');
            $table->boolean('restock_notified')->default(false)->after('was_out_of_stock');
        });
    }

    public function down(): void
    {
        Schema::table('wishlists', function (Blueprint $table) {
            $table->dropColumn(['price_at_added', 'price_drop_notified', 'was_out_of_stock', 'restock_notified']);
        });
    }
};
