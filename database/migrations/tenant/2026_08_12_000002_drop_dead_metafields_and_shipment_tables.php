<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Metafields and shipments were fully built (controller + model + routes)
 * but never had a UI: zero .vue files anywhere reference either feature, so
 * they were unreachable by any real user. Unlike Attributes (which blocked
 * a live feature — variant creation), nothing depends on these; removed
 * rather than finishing, per the audit's own "started and abandoned"
 * assessment. The old create-table migrations are left in place as
 * historical record — this migration only drops what they created.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('shipment_items');
        Schema::dropIfExists('shipments');
        Schema::dropIfExists('metafields');
    }

    public function down(): void
    {
        Schema::create('metafields', function (Blueprint $table) {
            $table->id();
            $table->string('owner_type', 50);
            $table->unsignedBigInteger('owner_id');
            $table->string('key', 100);
            $table->text('value')->nullable();
            $table->enum('type', ['text', 'number', 'boolean', 'date', 'url', 'json'])->default('text');
            $table->timestamps();
            $table->unique(['owner_type', 'owner_id', 'key']);
            $table->index(['owner_type', 'owner_id']);
        });

        Schema::create('shipments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->string('tracking_number')->nullable();
            $table->string('carrier')->nullable();
            $table->timestamp('shipped_at')->nullable();
            $table->enum('status', ['pending', 'packed', 'shipped', 'delivered'])->default('pending');
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('shipment_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shipment_id')->constrained('shipments')->cascadeOnDelete();
            $table->foreignId('order_item_id')->constrained('order_items')->cascadeOnDelete();
            $table->integer('quantity');
        });
    }
};
