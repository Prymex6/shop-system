<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Tenant deletion used to be a single irreversible, synchronous database drop
 * with no confirmation and no recovery window — one click (or a mis-click, or
 * a replayed request) permanently destroyed a real shop's entire database.
 * This adds a grace-period state: destroy() now only flags the tenant, and a
 * scheduled command performs the actual drop after several days, giving a
 * window to notice and undo the mistake.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Raw MySQL ALTER...MODIFY isn't portable (breaks the SQLite test suite),
        // and Laravel's own enum ->change() needs doctrine/dbal, which isn't
        // installed here. status is only ever constrained at the application
        // layer (validation, model casts) anyway, so drop the DB-level ENUM
        // constraint entirely and store it as a plain string — this works
        // identically on MySQL and SQLite with no extra dependency.
        if (Schema::getConnection()->getDriverName() === 'sqlite') {
            Schema::table('tenants', function (Blueprint $table) {
                $table->string('status')->default('trial')->change();
            });
        } else {
            DB::statement("ALTER TABLE tenants MODIFY status VARCHAR(30) NOT NULL DEFAULT 'trial'");
        }

        Schema::table('tenants', function (Blueprint $table) {
            $table->timestamp('deletion_requested_at')->nullable()->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropColumn('deletion_requested_at');
        });
    }
};
