<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * Uruchomienie:
     *   php artisan db:seed                    → tylko central DB (plany + super admin)
     *   php artisan tenants:seed               -> TenantSeeder for every active tenant
     *   php artisan db:seed --class=TenantSeeder (inside a tenant context)
     */
    public function run(): void
    {
        // ── Central (landlord) DB ──────────────────────────────────────────────
        // Subscription plans and the super admin go into the central database.
        $this->call(LandlordSeeder::class);

        $this->command->info('');
        $this->command->info('Central database seeded. To seed a tenant database run:');
        $this->command->info('  php artisan tenants:seed');
        $this->command->info('  or, inside a tenant: php artisan db:seed --class=TenantSeeder');
    }
}
