<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            // SQLite has no MODIFY COLUMN; widen the CHECK constraint by
            // swapping in a plain string column (native ADD/DROP/RENAME
            // COLUMN support, no doctrine/dbal needed since Laravel 11).
            Schema::table('email_campaigns', function (Blueprint $table) {
                $table->string('target_new', 20)->default('all')->after('target');
            });
            DB::statement('UPDATE email_campaigns SET target_new = target');
            Schema::table('email_campaigns', function (Blueprint $table) {
                $table->dropColumn('target');
            });
            Schema::table('email_campaigns', function (Blueprint $table) {
                $table->renameColumn('target_new', 'target');
            });

            return;
        }

        DB::statement("ALTER TABLE email_campaigns MODIFY COLUMN target ENUM('all','active','inactive','newsletter') NOT NULL DEFAULT 'all'");
    }

    public function down(): void
    {
        DB::statement("UPDATE email_campaigns SET target = 'all' WHERE target = 'newsletter'");

        if (DB::getDriverName() === 'sqlite') {
            Schema::table('email_campaigns', function (Blueprint $table) {
                $table->string('target_old', 20)->default('all')->after('target');
            });
            DB::statement('UPDATE email_campaigns SET target_old = target');
            Schema::table('email_campaigns', function (Blueprint $table) {
                $table->dropColumn('target');
            });
            Schema::table('email_campaigns', function (Blueprint $table) {
                $table->renameColumn('target_old', 'target');
            });

            return;
        }

        DB::statement("ALTER TABLE email_campaigns MODIFY COLUMN target ENUM('all','active','inactive') NOT NULL DEFAULT 'all'");
    }
};
