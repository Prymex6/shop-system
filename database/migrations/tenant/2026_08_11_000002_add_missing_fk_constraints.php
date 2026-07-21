<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Several *_id columns were indexed but never given a real FK constraint,
 * risking orphaned rows once the parent is deleted (e.g. a product_bundle_item
 * pointing at a variant_id that no longer exists, silently returning null
 * from the ->variant() relation instead of failing loudly). Verified against
 * all 77 tenant migrations — polymorphic *_id/*_type pairs (subject_id,
 * owner_id, etc.) were deliberately excluded, that's an intentional pattern,
 * not a bug.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_bundle_items', function (Blueprint $table) {
            $table->foreign('variant_id')->references('id')->on('product_variants')->nullOnDelete();
        });

        Schema::table('gift_cards', function (Blueprint $table) {
            $table->foreign('purchased_by_order_id')->references('id')->on('orders')->nullOnDelete();
        });

        Schema::table('loyalty_rewards', function (Blueprint $table) {
            $table->foreign('product_id')->references('id')->on('products')->nullOnDelete();
            $table->foreign('variant_id')->references('id')->on('product_variants')->nullOnDelete();
        });

        Schema::table('customers', function (Blueprint $table) {
            $table->foreign('loyalty_referred_by')->references('id')->on('customers')->nullOnDelete();
        });

        // staff_user_id on these two is NOT NULL (no doctrine/dbal installed
        // to safely change it nullable here), so nullOnDelete isn't an
        // option — cascadeOnDelete instead: a hard-deleted staff account's
        // reports/push subscriptions are cleaned up with it rather than
        // left dangling.
        Schema::table('staff_reports', function (Blueprint $table) {
            $table->foreign('staff_user_id')->references('id')->on('users')->cascadeOnDelete();
        });

        Schema::table('push_subscriptions', function (Blueprint $table) {
            $table->foreign('staff_user_id')->references('id')->on('users')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('product_bundle_items', fn (Blueprint $table) => $table->dropForeign(['variant_id']));
        Schema::table('gift_cards', fn (Blueprint $table) => $table->dropForeign(['purchased_by_order_id']));
        Schema::table('loyalty_rewards', function (Blueprint $table) {
            $table->dropForeign(['product_id']);
            $table->dropForeign(['variant_id']);
        });
        Schema::table('customers', fn (Blueprint $table) => $table->dropForeign(['loyalty_referred_by']));
        Schema::table('staff_reports', fn (Blueprint $table) => $table->dropForeign(['staff_user_id']));
        Schema::table('push_subscriptions', fn (Blueprint $table) => $table->dropForeign(['staff_user_id']));
    }
};
