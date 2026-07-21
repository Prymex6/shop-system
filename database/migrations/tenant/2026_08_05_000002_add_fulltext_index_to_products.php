<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

/**
 * Product search (ShopController::search / searchSuggest) was doing a
 * LIKE '%term%' scan with a leading wildcard, which can never use a normal
 * index — every keystroke on the autocomplete triggered a full table scan
 * competing with checkout for the same DB connections. FULLTEXT lets MySQL
 * search by matching words instead of scanning every row.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::getConnection()->getDriverName() !== 'mysql') {
            return;
        }

        Schema::getConnection()->statement(
            'ALTER TABLE products ADD FULLTEXT search_index (name, short_description)'
        );
    }

    public function down(): void
    {
        if (Schema::getConnection()->getDriverName() !== 'mysql') {
            return;
        }

        Schema::getConnection()->statement('ALTER TABLE products DROP INDEX search_index');
    }
};
