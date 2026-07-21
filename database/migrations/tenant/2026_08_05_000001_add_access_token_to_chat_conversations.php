<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * chat_conversations.access_token — a per-conversation secret checked on every
 * send/poll request so a visitor can only read or post to their own chat,
 * not any conversation by guessing its sequential ID.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('chat_conversations', function (Blueprint $table) {
            $table->string('access_token', 64)->nullable()->after('id');
        });

        // Backfill existing rows so old conversations aren't left permanently inaccessible.
        DB::table('chat_conversations')->whereNull('access_token')->orderBy('id')->chunkById(500, function ($rows) {
            foreach ($rows as $row) {
                DB::table('chat_conversations')->where('id', $row->id)->update(['access_token' => Str::random(64)]);
            }
        });
    }

    public function down(): void
    {
        Schema::table('chat_conversations', function (Blueprint $table) {
            $table->dropColumn('access_token');
        });
    }
};
