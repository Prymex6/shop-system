<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenant_modifications', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique()->comment('Unique slug, e.g. custom-menu-sort');
            $table->text('description')->nullable();
            $table->string('version', 20)->default('1.0.0');
            $table->string('author')->nullable();
            $table->boolean('status')->default(false)->comment('Globally enabled?');
            $table->json('tenant_ids')->nullable()->comment('null = all, [] = none, ["id1","id2"] = specific');
            $table->json('rules')->nullable()->comment('Patch rules: { patches: [{ file, operations[] }] }');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenant_modifications');
    }
};
