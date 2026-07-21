<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->enum('fulfillment_status', ['unfulfilled', 'processing', 'shipped', 'delivered', 'cancelled'])
                ->default('unfulfilled')
                ->after('bundle_name');
        });
    }

    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropColumn('fulfillment_status');
        });
    }
};
