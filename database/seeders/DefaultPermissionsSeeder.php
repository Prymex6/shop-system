<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Production seeder – run this after creating a new tenant.
 * Seeds only default role permissions and minimal settings,
 * WITHOUT any demo data (no sample products, orders, or test accounts).
 *
 * Usage: php artisan tenants:run db:seed --option="class=DefaultPermissionsSeeder"
 */
class DefaultPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Seeding default role permissions...');

        $defaults = [
            // Chef: can see orders (KDS), change order status
            ['role' => 'chef',   'permission' => 'view_orders'],
            ['role' => 'chef',   'permission' => 'update_order_status'],

            // Waiter: POS, take phone orders, view tables, accept/complete orders
            ['role' => 'waiter', 'permission' => 'view_orders'],
            ['role' => 'waiter', 'permission' => 'accept_phone_orders'],
            ['role' => 'waiter', 'permission' => 'manage_reservations'],
            ['role' => 'waiter', 'permission' => 'update_order_status'],

            // Cashier: POS + cash handling + accept orders
            ['role' => 'cashier', 'permission' => 'view_orders'],
            ['role' => 'cashier', 'permission' => 'accept_phone_orders'],
            ['role' => 'cashier', 'permission' => 'view_cash'],
            ['role' => 'cashier', 'permission' => 'update_order_status'],

            // Driver: view and complete own deliveries
            ['role' => 'driver', 'permission' => 'view_orders'],
            ['role' => 'driver', 'permission' => 'update_order_status'],
        ];

        foreach ($defaults as $perm) {
            DB::table('role_permissions')->updateOrInsert(
                ['role' => $perm['role'], 'permission' => $perm['permission']],
                ['created_at' => now(), 'updated_at' => now()]
            );
        }

        $this->command->info('Default role permissions seeded successfully.');
    }
}
