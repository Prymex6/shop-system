<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Files attached to digital products
        Schema::create('digital_files', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->string('name', 200)->comment('Display name');
            $table->string('path')->comment('Storage path');
            $table->string('disk', 50)->default('local');
            $table->unsignedBigInteger('size')->nullable()->comment('File size in bytes');
            $table->string('mime_type', 100)->nullable();
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        // Download links generated after purchase
        Schema::create('download_links', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->foreignId('order_item_id')->constrained('order_items')->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained('products')->nullOnDelete();
            $table->foreignId('file_id')->nullable()->constrained('digital_files')->nullOnDelete();
            $table->string('token', 100)->unique();
            $table->timestamp('expires_at')->nullable();
            $table->unsignedInteger('downloads_count')->default(0);
            $table->unsignedInteger('download_limit')->nullable()->comment('Null = unlimited');
            $table->timestamps();

            $table->index('token');
            $table->index('order_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('download_links');
        Schema::dropIfExists('digital_files');
    }
};
