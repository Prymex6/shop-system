<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
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
    }

    public function down(): void
    {
        Schema::dropIfExists('metafields');
    }
};
