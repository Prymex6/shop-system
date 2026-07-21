<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // SQLite stores enums as text – no ALTER needed in tests
        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        DB::statement("ALTER TABLE `users` MODIFY `role` ENUM('manager','chef','waiter','driver','cashier') NOT NULL DEFAULT 'waiter'");
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        DB::statement("ALTER TABLE `users` MODIFY `role` ENUM('manager','chef','waiter','driver') NOT NULL DEFAULT 'waiter'");
    }
};
