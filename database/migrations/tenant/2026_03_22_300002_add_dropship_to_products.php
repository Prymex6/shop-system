<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->boolean('is_dropship')->default(false)->after('is_featured');
            $table->foreignId('dropship_supplier_id')->nullable()->after('is_dropship')
                ->constrained('suppliers')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropForeign(['dropship_supplier_id']);
            $table->dropColumn(['is_dropship', 'dropship_supplier_id']);
        });
    }
};
