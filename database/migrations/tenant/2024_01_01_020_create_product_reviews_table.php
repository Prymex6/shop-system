<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();
            $table->foreignId('order_id')->nullable()->constrained('orders')->nullOnDelete();
            $table->unsignedTinyInteger('rating')->comment('1-5');
            $table->string('title', 200)->nullable();
            $table->text('body')->nullable();
            $table->json('images')->nullable()->comment('Array of image paths');
            $table->boolean('is_approved')->default(false)->index();
            $table->boolean('is_verified_purchase')->default(false);
            $table->text('reply')->nullable()->comment('Manager reply');
            $table->timestamp('replied_at')->nullable();

            // Guest reviewer (when customer deletes account)
            $table->string('reviewer_name', 100)->nullable();
            $table->string('reviewer_email', 150)->nullable();

            $table->timestamps();

            $table->index(['product_id', 'is_approved']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_reviews');
    }
};
