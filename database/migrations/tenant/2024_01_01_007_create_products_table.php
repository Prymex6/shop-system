<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->nullable()->constrained('categories')->nullOnDelete();

            // Basic info
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('short_description')->nullable();
            $table->longText('description')->nullable();
            $table->string('sku', 100)->nullable()->unique();

            // Type
            $table->enum('type', ['physical', 'digital'])->default('physical')->index();

            // Pricing
            $table->decimal('price', 10, 2);
            $table->decimal('compare_price', 10, 2)->nullable()->comment('Original price for showing discount');
            $table->decimal('cost_price', 10, 2)->nullable()->comment('Internal cost, not shown to customer');

            // Tax
            $table->foreignId('tax_rate_id')->nullable()->constrained('tax_rates')->nullOnDelete();

            // Physical product — stock & dimensions
            $table->boolean('track_stock')->default(true)->index();
            $table->integer('stock_quantity')->default(0);
            $table->integer('low_stock_threshold')->default(5);
            $table->boolean('allow_backorder')->default(false);
            $table->decimal('weight', 8, 3)->nullable()->comment('kg');
            $table->decimal('length', 8, 2)->nullable()->comment('cm');
            $table->decimal('width', 8, 2)->nullable()->comment('cm');
            $table->decimal('height', 8, 2)->nullable()->comment('cm');

            // Digital product
            $table->integer('download_limit')->nullable()->comment('Max downloads per purchase, null = unlimited');
            $table->integer('download_expires_hours')->nullable()->comment('Link expiry in hours, null = never');

            // Media
            $table->string('image')->nullable()->comment('Primary image path');
            $table->json('gallery')->nullable()->comment('["path1.jpg","path2.jpg"]');

            // Tags & meta
            $table->json('tags')->nullable();
            $table->string('meta_title', 200)->nullable();
            $table->text('meta_description')->nullable();

            // Status & visibility
            $table->boolean('is_published')->default(false)->index();
            $table->boolean('is_featured')->default(false)->index();
            $table->integer('sort_order')->default(0);

            // Stats (cached)
            $table->unsignedInteger('reviews_count')->default(0);
            $table->decimal('reviews_avg', 3, 2)->default(0);
            $table->unsignedInteger('sales_count')->default(0);

            $table->timestamps();

            $table->index(['is_published', 'type']);
            $table->index('category_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
