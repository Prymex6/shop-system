<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Needed to detect self-referral farming: one person registering a second
 * account with their own referral_code to pay themselves the loyalty bonus.
 * Storing the registration IP lets the referral-bonus award check whether
 * the referrer and the referred customer registered from the same address.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->string('registration_ip', 45)->nullable()->after('referral_bonus_awarded');
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn('registration_ip');
        });
    }
};
