<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class LandlordSeeder extends Seeder
{
    public function run(): void
    {
        // ── Plany subskrypcji ──────────────────────────────────────────────
        $plans = [
            [
                'name' => 'Starter',
                'slug' => 'starter',
                'price' => 49.00,
                'max_orders_per_month' => 300,
                'features' => json_encode([
                    'online_payments' => true,
                    'digital_products' => false,
                    'loyalty_program' => false,
                    'sms_notifications' => false,
                    'email_campaigns' => false,
                    'custom_css' => false,
                    'analytics' => false,
                    'multi_warehouse' => false,
                    'abandoned_cart' => false,
                    'max_products' => 30,
                    'max_staff' => 3,
                    'shipping_zones' => 2,
                ]),
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Basic',
                'slug' => 'basic',
                'price' => 99.00,
                'max_orders_per_month' => 1000,
                'features' => json_encode([
                    'online_payments' => true,
                    'digital_products' => true,
                    'loyalty_program' => false,
                    'sms_notifications' => false,
                    'email_campaigns' => false,
                    'custom_css' => false,
                    'analytics' => true,
                    'multi_warehouse' => false,
                    'abandoned_cart' => true,
                    'max_products' => 100,
                    'max_staff' => 10,
                    'shipping_zones' => 5,
                ]),
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Pro',
                'slug' => 'pro',
                'price' => 199.00,
                'max_orders_per_month' => 5000,
                'features' => json_encode([
                    'online_payments' => true,
                    'digital_products' => true,
                    'loyalty_program' => true,
                    'sms_notifications' => true,
                    'email_campaigns' => true,
                    'custom_css' => true,
                    'analytics' => true,
                    'multi_warehouse' => true,
                    'abandoned_cart' => true,
                    'thermal_printer' => true,
                    'max_products' => 500,
                    'max_staff' => 30,
                    'shipping_zones' => 15,
                ]),
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Premium',
                'slug' => 'premium',
                'price' => 399.00,
                'max_orders_per_month' => null,
                'features' => json_encode([
                    'online_payments' => true,
                    'digital_products' => true,
                    'loyalty_program' => true,
                    'sms_notifications' => true,
                    'email_campaigns' => true,
                    'custom_css' => true,
                    'analytics' => true,
                    'multi_warehouse' => true,
                    'abandoned_cart' => true,
                    'thermal_printer' => true,
                    'max_products' => null,
                    'max_staff' => null,
                    'shipping_zones' => null,
                    'custom_domain' => true,
                    'priority_support' => true,
                    'api_access' => true,
                ]),
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        DB::connection('central')->table('plans')->insertOrIgnore($plans);

        // ── Super Admin ────────────────────────────────────────────────────
        DB::connection('central')->table('super_admins')->insertOrIgnore([
            'name' => 'Administrator',
            'email' => config('platform.admin.email'),
            'password' => Hash::make(config('platform.admin.password') ?: Str::random(32)),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // E2E test super admin
        DB::connection('central')->table('super_admins')->insertOrIgnore([
            'name' => 'E2E Test Admin',
            'email' => 'admin@shop.localhost',
            'password' => Hash::make('password'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->command->info('  ✔ Plany i super admin gotowe.');
    }
}
