<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // SQLite's TEXT has no practical length cap (unlike MySQL's ~64KB TEXT) —
        // nothing to widen there, and SQLite doesn't support MODIFY COLUMN at all.
        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        // custom_css (and any other free-form setting) can legitimately need
        // more than TEXT's ~64KB cap — e.g. a manager pasting a full exported
        // theme stylesheet. MEDIUMTEXT raises that to ~16MB. Raw SQL because
        // doctrine/dbal (required by Schema::table()->change()) isn't installed.
        DB::statement('ALTER TABLE tenant_settings MODIFY value MEDIUMTEXT NULL');
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        DB::statement('ALTER TABLE tenant_settings MODIFY value TEXT NULL');
    }
};
