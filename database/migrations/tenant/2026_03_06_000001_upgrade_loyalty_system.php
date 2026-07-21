<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Extend customers table
        Schema::table('customers', function (Blueprint $table) {
            if (!Schema::hasColumn('customers', 'loyalty_points_earned_total')) {
                $table->integer('loyalty_points_earned_total')->default(0)->after('loyalty_points')
                    ->comment('Cumulative points ever earned (used for tier calculation)');
            }
            if (!Schema::hasColumn('customers', 'date_of_birth')) {
                $table->date('date_of_birth')->nullable()->after('loyalty_points_earned_total');
            }
            if (!Schema::hasColumn('customers', 'loyalty_birthday_rewarded_at')) {
                $table->date('loyalty_birthday_rewarded_at')->nullable()->after('date_of_birth');
            }
            if (!Schema::hasColumn('customers', 'loyalty_referred_by')) {
                $table->unsignedBigInteger('loyalty_referred_by')->nullable()->after('loyalty_birthday_rewarded_at');
            }
            if (!Schema::hasColumn('customers', 'marketing_opt_out')) {
                $table->boolean('marketing_opt_out')->default(false)->after('loyalty_referred_by');
            }
        });

        // Extend loyalty_points table
        Schema::table('loyalty_points', function (Blueprint $table) {
            if (!Schema::hasColumn('loyalty_points', 'expires_at')) {
                $table->timestamp('expires_at')->nullable()->after('order_id');
            }
        });

        // Loyalty rewards (nagrody do wymiany)
        Schema::create('loyalty_rewards', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('description')->nullable();
            $table->integer('cost_points')->comment('Points required to redeem');
            $table->string('type', 30)->comment('free_delivery|fixed_discount|percent_discount|free_product');
            $table->decimal('value', 8, 2)->default(0)->comment('PLN amount, % or product_id');
            $table->unsignedBigInteger('product_id')->nullable()->comment('For free_product type');
            $table->boolean('is_active')->default(true);
            $table->timestamp('valid_from')->nullable();
            $table->timestamp('valid_until')->nullable();
            $table->integer('max_uses')->nullable()->comment('Null = unlimited');
            $table->integer('used_count')->default(0);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        // Loyalty redemptions (historia wymian)
        Schema::create('loyalty_redemptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->foreignId('reward_id')->nullable()->constrained('loyalty_rewards')->nullOnDelete();
            $table->foreignId('order_id')->nullable()->constrained('orders')->nullOnDelete();
            $table->integer('points_spent');
            $table->decimal('discount_value', 8, 2)->default(0);
            $table->string('reward_type', 30)->nullable();
            $table->string('description')->nullable();
            $table->timestamps();

            $table->index(['customer_id', 'created_at']);
        });

        // Loyalty campaigns (kampanie mnożnikowe)
        Schema::create('loyalty_campaigns', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->decimal('multiplier', 4, 2)->default(2.00)->comment('E.g. 2.0 = double points');
            $table->string('applies_to', 20)->default('all')->comment('all|category|product');
            $table->unsignedBigInteger('target_id')->nullable()->comment('Category or product ID');
            $table->tinyInteger('day_of_week')->nullable()->comment('0=Sun,1=Mon...6=Sat, null=every day');
            $table->timestamp('valid_from')->nullable();
            $table->timestamp('valid_until')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('loyalty_campaigns');
        Schema::dropIfExists('loyalty_redemptions');
        Schema::dropIfExists('loyalty_rewards');

        Schema::table('loyalty_points', function (Blueprint $table) {
            $table->dropColumn('expires_at');
        });

        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn([
                'loyalty_points_earned_total',
                'date_of_birth',
                'loyalty_birthday_rewarded_at',
                'loyalty_referred_by',
            ]);
        });
    }
};
