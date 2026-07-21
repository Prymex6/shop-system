<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_number', 50)->unique();

            // Status
            $table->enum('status', ['pending', 'confirmed', 'paid', 'processing', 'shipped', 'delivered', 'cancelled', 'refunded'])
                ->default('pending')
                ->index();
            $table->enum('fulfillment_status', ['unfulfilled', 'processing', 'shipped', 'delivered', 'cancelled'])
                ->default('unfulfilled')
                ->index();

            // Relations
            $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();
            $table->foreignId('discount_code_id')->nullable()->constrained('discount_codes')->nullOnDelete();
            $table->foreignId('shipping_method_id')->nullable()->constrained('shipping_methods')->nullOnDelete();

            // Customer info snapshot
            $table->string('customer_name');
            $table->string('customer_email')->nullable();
            $table->string('customer_phone', 20)->nullable();

            // Addresses (JSON snapshots)
            $table->json('shipping_address')->nullable()->comment('{"name","street","city","postal_code","country","phone"}');
            $table->json('billing_address')->nullable()->comment('Same structure as shipping_address');

            // Financials
            $table->decimal('subtotal', 10, 2);
            $table->decimal('shipping_cost', 10, 2)->default(0);
            $table->decimal('discount', 10, 2)->default(0);
            $table->decimal('tax', 10, 2)->default(0);
            $table->decimal('total', 10, 2);
            $table->string('currency', 3)->default('PLN');

            // Payment
            $table->enum('payment_method', ['przelewy24', 'payu', 'tpay', 'stripe', 'bank_transfer', 'cash_on_delivery'])->nullable();
            $table->enum('payment_status', ['pending', 'awaiting_payment', 'paid', 'failed', 'refunded', 'partially_refunded'])
                ->default('pending')
                ->index();
            $table->timestamp('paid_at')->nullable();
            $table->json('payment_data')->nullable();

            // Shipping / Fulfillment
            $table->string('tracking_number')->nullable();
            $table->string('tracking_carrier')->nullable();
            $table->timestamp('shipped_at')->nullable();
            $table->timestamp('delivered_at')->nullable();

            // Digital downloads token (for orders with digital products)
            $table->string('download_token', 100)->nullable()->unique()->index();
            $table->timestamp('download_token_expires_at')->nullable();

            // Invoice
            $table->string('invoice_number', 50)->nullable();
            $table->boolean('invoice_requested')->default(false);
            $table->json('invoice_data')->nullable()->comment('Company NIP, name, address');

            // Misc
            $table->text('notes')->nullable()->comment('Customer notes');
            $table->text('internal_notes')->nullable()->comment('Staff notes, not visible to customer');

            $table->timestamps();

            $table->index('created_at');
            $table->index(['customer_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
