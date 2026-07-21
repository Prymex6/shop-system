<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gift_cards', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique();
            $table->decimal('initial_value', 10, 2);
            $table->decimal('current_value', 10, 2);
            $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();
            $table->unsignedBigInteger('purchased_by_order_id')->nullable()->index();
            $table->timestamp('expires_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['code', 'is_active']);
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->unsignedBigInteger('gift_card_id')->nullable()->after('discount_code_id');
            $table->decimal('gift_card_discount', 10, 2)->default(0)->after('gift_card_id');

            $table->foreign('gift_card_id')->references('id')->on('gift_cards')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['gift_card_id']);
            $table->dropColumn(['gift_card_id', 'gift_card_discount']);
        });

        Schema::dropIfExists('gift_cards');
    }
};
