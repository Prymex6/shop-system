<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tax_rates', function (Blueprint $table) {
            $table->string('country_code', 2)->nullable()->after('rate');
            $table->boolean('is_eu_oss')->default(false)->after('country_code');
            $table->decimal('eu_vat_rate', 5, 2)->nullable()->after('is_eu_oss');
        });
    }

    public function down(): void
    {
        Schema::table('tax_rates', function (Blueprint $table) {
            $table->dropColumn(['country_code', 'is_eu_oss', 'eu_vat_rate']);
        });
    }
};
