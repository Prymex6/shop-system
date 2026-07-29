<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
|--------------------------------------------------------------------------
| Scheduled Commands
|--------------------------------------------------------------------------
|
| None of these background jobs were previously registered anywhere, so
| loyalty points never expired, abandoned-cart recovery emails never sent,
| expired flash sales stayed "active" forever, low-stock reorder alerts
| never fired, and tenants flagged for deletion never actually got cleaned
| up. Each command already loops over all tenants internally.
|
*/

Schedule::command('shop:process-abandoned-carts')->everyThirtyMinutes()->withoutOverlapping();
Schedule::command('wishlist:process-alerts')->hourly()->withoutOverlapping();
Schedule::command('loyalty:expire-points')->dailyAt('02:00')->withoutOverlapping();
Schedule::command('loyalty:process-tier-degradation')->dailyAt('02:15')->withoutOverlapping();
Schedule::command('loyalty:process-badges')->dailyAt('02:30')->withoutOverlapping();
Schedule::command('loyalty:monthly-bonus')->monthlyOn(1, '03:00')->withoutOverlapping();
Schedule::command('flash-sales:deactivate-expired')->everyFifteenMinutes()->withoutOverlapping();
Schedule::command('inventory:check-reorder-points')->dailyAt('07:00')->withoutOverlapping();
Schedule::command('shop:send-weekly-reports')->weeklyOn(1, '08:00')->withoutOverlapping();
Schedule::command('shop:send-weekly-reports --monthly')->monthlyOn(1, '08:30')->withoutOverlapping();
Schedule::command('tenants:prune-pending-deletion')->dailyAt('03:30')->withoutOverlapping()->onOneServer();
Schedule::command('backup:tenant')->dailyAt('04:00')->withoutOverlapping()->onOneServer();
Schedule::command('orders:expire-unpaid')->everyThirtyMinutes()->withoutOverlapping();
Schedule::command('shipments:sync-status')->hourly()->withoutOverlapping();
