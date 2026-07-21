<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('refunds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->decimal('amount', 10, 2);
            $table->string('reason', 300)->nullable();
            $table->text('notes')->nullable();
            $table->enum('status', ['pending', 'approved', 'processing', 'completed', 'rejected'])->default('pending')->index();
            $table->string('gateway_refund_id')->nullable()->comment('Refund ID from payment gateway');
            $table->json('items')->nullable()->comment('Which order items are being refunded');
            $table->json('images')->nullable()->comment('Customer-uploaded photos of damaged goods');
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('refunds');
    }
};
