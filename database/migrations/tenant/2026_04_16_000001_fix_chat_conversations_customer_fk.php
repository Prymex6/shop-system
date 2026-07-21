<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Fix chat_conversations.customer_id foreign key — it should reference
 * the `customers` table (logged-in shop customers), not the `users` table
 * (staff / manager accounts).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('chat_conversations', function (Blueprint $table) {
            // Drop the incorrect FK constraint pointing to `users`
            $table->dropForeign(['customer_id']);

            // Re-add as a plain nullable unsigned big integer (no FK constraint).
            // A soft reference to `customers.id` — no DB-level FK because
            // customers can be anonymised / deleted independently.
            $table->unsignedBigInteger('customer_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('chat_conversations', function (Blueprint $table) {
            $table->dropColumn('customer_id');
            $table->foreignId('customer_id')->nullable()->after('id')
                ->constrained('users')->nullOnDelete();
        });
    }
};
