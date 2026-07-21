<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('badges', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('icon')->nullable();
            $table->enum('condition_type', ['orders_count', 'total_spent', 'review_count', 'referral_count'])->nullable();
            $table->integer('condition_value')->nullable();
            $table->timestamps();
        });

        Schema::create('customer_badges', function (Blueprint $table) {
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->foreignId('badge_id')->constrained('badges')->cascadeOnDelete();
            $table->timestamp('awarded_at')->useCurrent();
            $table->primary(['customer_id', 'badge_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_badges');
        Schema::dropIfExists('badges');
    }
};
