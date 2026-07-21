<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * ContactController::send() used to fire off a raw inline email and return
 * "success" regardless of whether the mail actually sent — if shop_email
 * wasn't configured, the message vanished with no record anywhere. Mirrors
 * the landlord's contact_inquiries table so the message is durably captured
 * before mail delivery is even attempted.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contact_inquiries', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email');
            $table->text('message');
            $table->boolean('read')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contact_inquiries');
    }
};
