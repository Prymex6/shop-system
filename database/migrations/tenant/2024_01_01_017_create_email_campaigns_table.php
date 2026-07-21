<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('email_campaigns', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('subject');
            $table->longText('content')->comment('HTML content of the email');
            $table->enum('status', ['draft', 'sending', 'sent', 'cancelled'])->default('draft')->index();
            $table->enum('target', ['all', 'active', 'inactive'])->default('all')
                ->comment('all = everyone, active = ordered in 90d, inactive = not ordered in 90d');
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->integer('recipients_count')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_campaigns');
    }
};
