<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * FraudDetectionService's IP-velocity rule queried payment_data->ip, a key
 * nothing in the app ever wrote — the check always returned zero. Store the
 * IP directly on the order instead of nesting it in the payment-gateway JSON.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('customer_ip', 45)->nullable()->after('customer_phone')->index();
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('customer_ip');
        });
    }
};
