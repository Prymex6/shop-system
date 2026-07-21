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
     *   php artisan tenants:seed               → TenantSeeder dla każdego aktywnego tenanta
     *   php artisan db:seed --class=TenantSeeder (wewnątrz kontekstu tenanta)
     */
    public function run(): void
    {
        // ── Central (landlord) DB ──────────────────────────────────────────────
        // Plany subskrypcji i super admin zapisywane są do bazy centralnej.
        $this->call(LandlordSeeder::class);

        $this->command->info('');
        $this->command->info('Central DB zasilona. Aby zasilić tenant DB uruchom:');
        $this->command->info('  php artisan tenants:seed');
        $this->command->info('  lub wewnątrz tenanta: php artisan db:seed --class=TenantSeeder');
    }
}
