<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shipping_methods', function (Blueprint $table) {
            $table->id();
            $table->string('name', 150);
            $table->string('description', 300)->nullable();
            $table->enum('type', ['standard', 'express', 'pickup', 'digital'])->default('standard');
            $table->decimal('price', 10, 2)->default(0);
            $table->decimal('free_from', 10, 2)->nullable()->comment('Free shipping when order >= this amount');
            $table->integer('delivery_days_min')->default(1);
            $table->integer('delivery_days_max')->default(3);
            $table->decimal('weight_min', 8, 3)->nullable()->comment('kg');
            $table->decimal('weight_max', 8, 3)->nullable()->comment('kg');
            $table->boolean('is_active')->default(true)->index();
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('shipping_zones', function (Blueprint $table) {
            $table->id();
            $table->string('name', 150);
            $table->json('countries')->comment('["PL","DE","CZ"]');
            $table->boolean('is_default')->default(false);
            $table->timestamps();
        });

        Schema::create('shipping_zone_methods', function (Blueprint $table) {
            $table->id();
            $table->foreignId('zone_id')->constrained('shipping_zones')->cascadeOnDelete();
            $table->foreignId('method_id')->constrained('shipping_methods')->cascadeOnDelete();
            $table->decimal('price_override', 10, 2)->nullable()->comment('Override method price for this zone');
            $table->decimal('free_from_override', 10, 2)->nullable();

            $table->unique(['zone_id', 'method_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shipping_zone_methods');
        Schema::dropIfExists('shipping_zones');
        Schema::dropIfExists('shipping_methods');
    }
};
