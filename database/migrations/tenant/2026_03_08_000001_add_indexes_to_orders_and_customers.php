<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // SQLite (used in tests) already has these indexes from the create migration — skip
        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        Schema::table('orders', function (Blueprint $table) {
            if (!Schema::hasIndex('orders', 'orders_status_index')) {
                $table->index('status');
            }
            if (!Schema::hasIndex('orders', 'orders_payment_status_index')) {
                $table->index('payment_status');
            }
        });

        Schema::table('customers', function (Blueprint $table) {
            if (!Schema::hasIndex('customers', 'customers_loyalty_tier_index')) {
                $table->index('loyalty_tier');
            }
        });
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->dropIndex(['payment_status']);
        });
        Schema::table('customers', function (Blueprint $table) {
            $table->dropIndex(['loyalty_tier']);
        });
    }
};
