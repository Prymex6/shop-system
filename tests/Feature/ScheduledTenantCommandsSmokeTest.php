<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Bus;
use Tests\LandlordTestCase;

/**
 * Round 23: ProcessAbandonedCarts, SendWeeklyReports, CreateTenantBackup and
 * (this session's new) ProcessWishlistAlerts all looped over tenants via
 * `\App\Models\Tenant::all()` — a class that has never existed in this
 * codebase (the real landlord model is `App\Models\Landlord\Tenant`,
 * aliased as `Tenant` via a `use` import everywhere it's used correctly,
 * e.g. FixLegalPlaceholders.php). Every one of these commands fataled with
 * "Class App\Models\Tenant not found" on every single run.
 *
 * This went undetected because every existing test for the *jobs* these
 * commands dispatch (SendAbandonedCartReminder, CreateBackup, ...)
 * instantiates and calls the job directly, bypassing the command's
 * tenant-loading line entirely — including in this session, until a test
 * for the newly-added ProcessWishlistAlerts hit the identical bug and
 * surfaced the pattern. Most consequentially: backup:tenant is scheduled
 * daily — if this is actually cron-driven in production, no tenant backup
 * has ever successfully been created.
 *
 * TenantTestCase can't catch this class of bug (its SQLite schema has no
 * `tenants` table at all — landlord-only). LandlordTestCase provides that
 * table; with zero tenants seeded, each command's loop body never
 * executes, but the fatal class-resolution error happens before the loop
 * even starts, so it's still caught here.
 */
class ScheduledTenantCommandsSmokeTest extends LandlordTestCase
{
    public function test_process_abandoned_carts_runs_without_crashing(): void
    {
        $exitCode = Artisan::call('shop:process-abandoned-carts');
        $this->assertEquals(0, $exitCode, Artisan::output());
    }

    public function test_process_wishlist_alerts_runs_without_crashing(): void
    {
        $exitCode = Artisan::call('wishlist:process-alerts');
        $this->assertEquals(0, $exitCode, Artisan::output());
    }

    public function test_send_weekly_reports_runs_without_crashing(): void
    {
        $exitCode = Artisan::call('shop:send-weekly-reports');
        $this->assertEquals(0, $exitCode, Artisan::output());
    }

    public function test_backup_tenant_runs_without_crashing(): void
    {
        Bus::fake();
        $exitCode = Artisan::call('backup:tenant');
        $this->assertEquals(0, $exitCode, Artisan::output());
    }
}
