<?php

namespace Tests;

use App\Models\Landlord\SuperAdmin;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

/**
 * Base test case for tests that need ONLY the landlord (central) database tables.
 *
 * Overrides the 'central' DB connection to use SQLite :memory: so it stays
 * isolated from the real MySQL central DB and gets a fresh schema per test.
 * Runs landlord migrations on that connection during setUp().
 *
 * Do NOT extend this for tests that also need tenant tables; the default
 * connection won't have tenant schema. Use TenantTestCase instead.
 */
abstract class LandlordTestCase extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Redirect 'central' to a fresh SQLite :memory: DB.
        // Must happen BEFORE DB::purge() and migrations.
        config(['database.connections.central' => [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
            'foreign_key_constraints' => true,
        ]]);

        // Force stancl/tenancy CentralConnection trait to use 'central' (not env('DB_CONNECTION')='sqlite').
        config(['tenancy.database.central_connection' => 'central']);

        // Discard any cached MySQL connection for 'central'.
        DB::purge('central');

        Artisan::call('migrate', [
            '--path' => 'database/migrations/landlord',
            '--database' => 'central',
            '--force' => true,
        ]);
    }

    /**
     * Create a super-admin user in the central DB for landlord route tests.
     */
    protected function superAdmin(): SuperAdmin
    {
        return SuperAdmin::create([
            'name' => 'Super Admin',
            'email' => 'admin@test.com',
            'password' => bcrypt('password'),
        ]);
    }
}
