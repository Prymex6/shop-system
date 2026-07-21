<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fraud_flags', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->string('flag_type', 100);
            $table->integer('score')->default(0);
            $table->json('details')->nullable();
            $table->timestamps();

            $table->index('order_id');
            $table->index('flag_type');
        });

        Schema::create('fraud_blocklist', function (Blueprint $table) {
            $table->id();
            $table->enum('type', ['email', 'ip', 'card_bin']);
            $table->string('value', 255);
            $table->string('reason', 500)->nullable();
            $table->timestamps();

            $table->unique(['type', 'value']);
            $table->index('type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fraud_blocklist');
        Schema::dropIfExists('fraud_flags');
    }
};
