<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // invoice_number already exists in 2024_01_01_013_create_orders_table.php.
        // Only add buyer_nip which is a new column for this system.
        Schema::table('orders', function (Blueprint $table) {
            $table->string('buyer_nip', 20)->nullable()->after('customer_email');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('buyer_nip');
        });
    }
};
