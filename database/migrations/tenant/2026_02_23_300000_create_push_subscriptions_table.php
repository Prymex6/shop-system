<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('push_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('staff_user_id');
            $table->text('endpoint');
            $table->string('p256dh_key')->nullable();
            $table->string('auth_key')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->unique('endpoint', 'unique_endpoint');
            $table->index('staff_user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('push_subscriptions');
    }
};
