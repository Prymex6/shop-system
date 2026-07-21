<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tax_rates', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100)->comment('e.g. "VAT 23%", "VAT 8%"');
            $table->decimal('rate', 5, 2)->comment('Percentage, e.g. 23.00');
            $table->string('country', 2)->nullable()->comment('ISO 2-letter country code, null = all');
            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tax_rates');
    }
};
