<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        DB::statement("ALTER TABLE `users` MODIFY `role` ENUM('manager','fulfillment','warehouse','cashier') NOT NULL DEFAULT 'manager'");
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        DB::statement("ALTER TABLE `users` MODIFY `role` ENUM('manager','chef','waiter','driver','cashier','fulfillment','warehouse') NOT NULL DEFAULT 'waiter'");
    }
};
