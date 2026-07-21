<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->string('referral_code', 10)->nullable()->unique()->after('loyalty_referred_by');
            $table->boolean('referral_bonus_awarded')->default(false)->after('referral_code');
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn(['referral_code', 'referral_bonus_awarded']);
        });
    }
};
