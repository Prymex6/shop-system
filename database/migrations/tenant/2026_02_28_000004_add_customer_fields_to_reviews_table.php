<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        // order_id and customer_id are already part of product_reviews table (migration 020).
        // The old `reviews` table no longer exists in shop-system.
    }

    public function down(): void {}
};
