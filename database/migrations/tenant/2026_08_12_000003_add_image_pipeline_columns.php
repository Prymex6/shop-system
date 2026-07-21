<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Product images were stored as the raw uploaded file — no resizing, no
 * re-encoding, no thumbnail — so the same multi-megabyte original was
 * served as both the full-size hero image and every 64x64 gallery
 * thumbnail, and the storefront had no width/height to reserve layout
 * space with (Cumulative Layout Shift risk on what's meant to be a TikTok
 * ad landing page). ProductImageProcessor now generates a size-capped hero
 * + a small thumbnail on upload; these columns store the results.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->unsignedInteger('image_width')->nullable()->after('image');
            $table->unsignedInteger('image_height')->nullable()->after('image_width');
        });

        Schema::table('product_images', function (Blueprint $table) {
            $table->string('thumbnail_path')->nullable()->after('path');
            $table->unsignedInteger('width')->nullable()->after('thumbnail_path');
            $table->unsignedInteger('height')->nullable()->after('width');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['image_width', 'image_height']);
        });

        Schema::table('product_images', function (Blueprint $table) {
            $table->dropColumn(['thumbnail_path', 'width', 'height']);
        });
    }
};
