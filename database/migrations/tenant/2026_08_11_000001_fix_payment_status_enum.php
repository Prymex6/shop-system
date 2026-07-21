<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * 2026_02_23_400000_extend_payment_method_enum narrowed the MySQL
 * payment_status enum to ('pending','awaiting_payment','paid','failed',
 * 'refunded') — dropping 'partially_refunded', which the original
 * create_orders_table migration had, and never adding 'refund_failed',
 * which OrderCancellationService writes on a failed gateway refund. Both
 * values are actively written by RefundService::process() and
 * OrderCancellationService::cancel() — on real MySQL (this migration is a
 * no-op on SQLite, where the narrower enum was never applied and the bug
 * doesn't reproduce, which is why the test suite never caught it) writing
 * either value throws a data-truncated/constraint error instead of saving.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        DB::statement("ALTER TABLE orders MODIFY COLUMN payment_status ENUM(
            'pending','awaiting_payment','paid','failed','refunded','partially_refunded','refund_failed'
        ) NOT NULL DEFAULT 'pending'");
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        DB::statement("UPDATE orders SET payment_status = 'failed' WHERE payment_status = 'refund_failed'");
        DB::statement("UPDATE orders SET payment_status = 'refunded' WHERE payment_status = 'partially_refunded'");
        DB::statement("ALTER TABLE orders MODIFY COLUMN payment_status ENUM(
            'pending','awaiting_payment','paid','failed','refunded'
        ) NOT NULL DEFAULT 'pending'");
    }
};
