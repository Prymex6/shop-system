<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Plan only had max_orders_per_month — no max_products/max_staff/
 * max_storage columns existed at all, so a plan could never actually
 * express those limits regardless of what the landlord typed into a
 * (nonexistent) form field. Adds the columns; max_products and max_staff
 * are enforced synchronously (cheap COUNT queries) in ProductController/
 * StaffController. max_storage_mb is intentionally NOT enforced yet — doing
 * that accurately requires either a running per-tenant storage counter
 * updated on every file write/delete, or an expensive full disk scan on
 * every upload, neither of which belongs in this migration-sized fix.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            $table->integer('max_products')->nullable()->after('max_orders_per_month')->comment('null = unlimited');
            $table->integer('max_staff')->nullable()->after('max_products')->comment('null = unlimited');
            $table->integer('max_storage_mb')->nullable()->after('max_staff')->comment('null = unlimited; not yet enforced');
        });
    }

    public function down(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            $table->dropColumn(['max_products', 'max_staff', 'max_storage_mb']);
        });
    }
};
