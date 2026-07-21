<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->addIndex('product_variants', 'product_id', 'product_variants_product_id_index');
        $this->addIndex('order_items', 'order_id', 'order_items_order_id_index');
        $this->addIndex('order_items', 'product_id', 'order_items_product_id_index');
        $this->addIndex('chat_messages', 'conversation_id', 'chat_messages_conversation_id_index');
        $this->addIndex('loyalty_points', 'expires_at', 'loyalty_points_expires_at_index');
        $this->addIndex('stock_movements', 'product_id', 'stock_movements_product_id_index');
    }

    public function down(): void
    {
        $this->dropIndexSafe('product_variants', 'product_variants_product_id_index');
        $this->dropIndexSafe('order_items', 'order_items_order_id_index');
        $this->dropIndexSafe('order_items', 'order_items_product_id_index');
        $this->dropIndexSafe('chat_messages', 'chat_messages_conversation_id_index');
        $this->dropIndexSafe('loyalty_points', 'loyalty_points_expires_at_index');
        $this->dropIndexSafe('stock_movements', 'stock_movements_product_id_index');
    }

    private function addIndex(string $table, string $column, string $indexName): void
    {
        if (!Schema::hasIndex($table, $indexName)) {
            Schema::table($table, function (Blueprint $t) use ($column) {
                $t->index($column);
            });
        }
    }

    private function dropIndexSafe(string $table, string $indexName): void
    {
        if (Schema::hasIndex($table, $indexName)) {
            Schema::table($table, function (Blueprint $t) use ($indexName) {
                $t->dropIndex($indexName);
            });
        }
    }
};
