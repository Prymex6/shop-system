<?php

namespace App\Providers;

use App\Events\OrderCreated;
use App\Events\OrderShipped;
use App\Http\Controllers\LandingController;
use App\Listeners\GenerateDigitalDownloads;
use App\Listeners\HandleDropshippingOnOrder;
use App\Listeners\PrintOrderReceipt;
use App\Listeners\SendDatabaseNotificationOnOrder;
use App\Listeners\SendOrderConfirmationSms;
use App\Listeners\SendPushNotificationOnOrder;
use App\Listeners\SendShippingNotificationSms;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Stancl\Tenancy\Middleware\InitializeTenancyByDomain;
use Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Register landlord (central DB) migrations so `php artisan migrate` finds them.
        // Tenant migrations are handled separately by `php artisan tenants:migrate`
        // via tenancy.migration_parameters pointing to database/migrations/tenant/.
        $this->loadMigrationsFrom(database_path('migrations/landlord'));

        // --- Landing page fix ---
        // TenancyServiceProvider registers tenant routes (including GET /) via app->booted(),
        // which fires AFTER web.php. Laravel stores routes keyed by domain+uri, so the tenant
        // GET / route replaces the central 'landing' route in the route collection.
        //
        // TenancyServiceProvider also calls makeTenancyMiddlewareHighestPriority(), which puts
        // PreventAccessFromCentralDomains at a HIGHER priority than InitializeTenancyByDomain.
        // So on a central domain request to GET /, PreventAccessFromCentralDomains runs FIRST
        // and aborts(404) before InitializeTenancyByDomain even runs.
        //
        // Fix: override PreventAccessFromCentralDomains::$abortRequest so that GET / on a
        // central domain (localhost) invokes LandingController instead of aborting 404.
        PreventAccessFromCentralDomains::$abortRequest = function ($request, $next) {
            if ($request->is('/')) {
                return app(LandingController::class)->index();
            }
            abort(404);
        };

        // Keep onFail as a safety net for the case PreventAccessFromCentralDomains doesn't fire.
        InitializeTenancyByDomain::$onFail = function ($e, $request, $next) {
            if ($request->is('/') && in_array($request->getHost(), config('tenancy.central_domains'))) {
                return app(LandingController::class)->index();
            }
            abort(404);
        };

        // Keyed by the pending 2FA user (session), not IP alone — otherwise an
        // attacker can multiply their guess budget against a single account by
        // rotating IPs/proxies while the session cookie stays constant.
        RateLimiter::for('2fa-verify', function ($request) {
            $key = $request->session()->get('2fa_user_id') ?: $request->ip();

            return Limit::perMinutes(60, 10)->by($key);
        });

        // Register event listeners
        Event::listen(OrderCreated::class, [GenerateDigitalDownloads::class, 'handle']);
        Event::listen(OrderCreated::class, [PrintOrderReceipt::class, 'handle']);
        Event::listen(OrderCreated::class, [SendPushNotificationOnOrder::class, 'handle']);
        Event::listen(OrderCreated::class, [SendOrderConfirmationSms::class, 'handle']);
        Event::listen(OrderCreated::class, [HandleDropshippingOnOrder::class, 'handle']);
        Event::listen(OrderCreated::class, [SendDatabaseNotificationOnOrder::class, 'handle']);
        Event::listen(OrderShipped::class, [SendShippingNotificationSms::class, 'handle']);

        // No Horizon in this app, and none of the 22 Mailables implement
        // ShouldQueue (Mailer::queue() pushes to the queue unconditionally
        // regardless of that interface) — without this, a failed order
        // confirmation email, digital-download SMS, etc. previously landed
        // silently in failed_jobs with zero signal to anyone until a manager
        // happened to check the table manually.
        Queue::failing(function (JobFailed $event) {
            Log::critical('Queue job failed', [
                'connection' => $event->connectionName,
                'job' => $event->job->resolveName(),
                'exception' => $event->exception->getMessage(),
            ]);
        });
    }
}
