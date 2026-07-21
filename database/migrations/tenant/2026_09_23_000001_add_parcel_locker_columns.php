<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Columns a parcel-locker delivery needs.
 *
 * A shipping method says which carrier fulfils it. Only methods that name
 * one get a pick-a-locker step at checkout, and only those can have a label
 * bought for them from the manager panel; everything else keeps working the
 * way it always did.
 *
 * The chosen locker is kept on the order twice over: its code, which is what
 * the carrier's API wants, and a copy of its address as it was shown to the
 * customer. Lockers get moved and decommissioned, so the copy is what an
 * order from six months ago can still be read against.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shipping_methods', function (Blueprint $table) {
            $table->string('carrier', 30)->nullable()->after('type');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->string('pickup_point_code', 30)->nullable()->after('shipping_address');
            $table->json('pickup_point_data')->nullable()->after('pickup_point_code');
        });
    }

    public function down(): void
    {
        Schema::table('shipping_methods', function (Blueprint $table) {
            $table->dropColumn('carrier');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['pickup_point_code', 'pickup_point_data']);
        });
    }
};
