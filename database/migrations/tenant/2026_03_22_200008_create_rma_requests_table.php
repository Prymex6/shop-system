<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rma_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->string('rma_number', 30)->unique();
            $table->string('customer_email');
            $table->string('customer_name');
            $table->enum('status', ['pending', 'approved', 'received', 'inspected', 'refunded', 'rejected'])->default('pending');
            $table->text('reason');
            $table->json('items');
            $table->text('condition_notes')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('received_at')->nullable();
            $table->decimal('refund_amount', 10, 2)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rma_requests');
    }
};
