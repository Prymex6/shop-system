<?php

namespace App\Console\Commands;

use App\Models\Landlord\SupportTicket;
use App\Models\Landlord\Tenant;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Stancl\Tenancy\Database\Models\Domain;

class SetupE2ETestEnvironment extends Command
{
    protected $signature = 'e2e:setup {--fresh : Re-seed tenant data (does not drop DB)}';

    protected $description = 'Set up test environment for Playwright E2E tests';

    public function handle(): int
    {
        if (app()->isProduction()) {
            $this->error('⛔ This command must NOT run in production.');

            return self::FAILURE;
        }

        $this->info('🧪 Setting up E2E test environment...');

        // 1. Ensure landlord data (plans + super admin)
        $this->call('db:seed', ['--class' => 'LandlordSeeder', '--force' => true]);

        // 2. Find the tenant at ecommerce.localhost (the development tenant)
        $domain = 'ecommerce.localhost';
        $domainRecord = Domain::where('domain', $domain)->first();

        if (!$domainRecord) {
            $this->error("No tenant found at {$domain}. Create a tenant with that domain first.");

            return self::FAILURE;
        }

        $tenant = Tenant::find($domainRecord->tenant_id);

        if (!$tenant) {
            $this->error("Tenant record not found for domain {$domain}.");

            return self::FAILURE;
        }

        $this->info("  Using tenant: {$tenant->id} ({$tenant->name}) → {$domain}");

        // 2b. Ensure tenant is active and license is valid for tests
        $tenant->update([
            'status' => 'active',
            'license_ends_at' => now()->addYear(),
        ]);
        $this->info('  Tenant status set to active with valid license.');

        // 3. Run tenant migrations
        $this->info('  Running tenant migrations...');
        tenancy()->initialize($tenant);

        $this->call('tenants:migrate', [
            '--tenants' => [$tenant->id],
            '--force' => true,
        ]);

        // Re-initialize tenancy after migrate (tenants:migrate may have ended it)
        tenancy()->initialize($tenant);

        // 4. Clear all tenant cache (rate limits stored in tenant DB cache)
        // Must be called while IN tenant context to clear the tenant's cache table.
        $this->call('cache:clear');
        $this->info('  Tenant cache cleared (rate limits reset).');

        // Clean up e2e-created test customers (e.g. from E23.2.3 registration test)
        DB::table('customers')
            ->where('email', 'like', 'e2e.%@example.com')
            ->delete();

        // Clean up e2e-created test staff employees (e.g. from staff management test)
        DB::table('users')
            ->where('email', 'like', 'e2e.%@test.com')
            ->orWhere('email', 'test.worker.e2e@example.com')
            ->delete();

        // Clean up e2e-created VAT rates (e.g. from tax management test)
        DB::table('tax_rates')
            ->where('name', 'like', '%E2E%')
            ->delete();

        // Clean up e2e-created categories (e.g. from kategorie test)
        DB::table('categories')
            ->where('name', 'like', '%E2E%')
            ->delete();

        // Clean up e2e-created test products (e.g. from E5.1.4)
        DB::table('products')
            ->where('name', 'like', '%E2E%')
            ->delete();

        // Clean up e2e-created blog articles (e.g. from blog E-MB.1.3)
        DB::table('articles')
            ->where('title', 'like', '%E2E%')
            ->delete();

        // Clean up e2e-created knowledge base articles (e.g. from E-MKB.1.3)
        DB::table('kb_articles')
            ->where('title', 'like', '%E2E%')
            ->orWhere('title', 'like', '%zwroty%')
            ->delete();

        // Clean up e2e-created chat conversations
        DB::table('chat_conversations')
            ->where('guest_email', 'like', 'e2e@%')
            ->delete();

        // 5. Seed tenant data
        $this->info('  Seeding tenant data...');
        $this->call('db:seed', [
            '--class' => 'TenantSeeder',
            '--force' => true,
        ]);

        tenancy()->end();

        // Clean up e2e-created support tickets in landlord DB (e.g. from support test)
        SupportTicket::where('subject', 'like', '%E2E%')->delete();

        // 5. Output connection info for Playwright
        $this->newLine();
        $this->info('✅ E2E test environment ready!');
        $this->table(
            ['Key', 'Value'],
            [
                ['Landlord URL',    'http://localhost:8000'],
                ['Tenant URL',      "http://{$domain}:8000"],
                ['Super Admin',     'admin@shop.localhost / password'],
                ['Manager',         'manager@example.com / password'],
                ['Staff',           'staff@example.com / password'],
                ['Customer 1',      'klient@example.pl / password'],
                ['Customer 2',      'maria@example.pl / password'],
                ['Tenant ID',       $tenant->id],
                ['Tenant Domain',   $domain],
            ]
        );

        return self::SUCCESS;
    }
}
