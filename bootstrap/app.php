<?php

use App\Http\Middleware\CheckPermission;
use App\Http\Middleware\CheckRole;
use App\Http\Middleware\EnsureCustomerAuth;
use App\Http\Middleware\EnsureSuperAdmin;
use App\Http\Middleware\EnsureTenantAuth;
use App\Http\Middleware\ForceHttps;
use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\RequiresPlanFeature;
use App\Http\Middleware\RequiresTwoFactor;
use App\Http\Middleware\SetCurrency;
use App\Http\Middleware\SetLocale;
use App\Http\Middleware\Tenant\CheckSetupComplete;
use App\Http\Middleware\Tenant\CheckTenantLicense;
use App\Http\Middleware\Tenant\EnsureInstallNotComplete;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\ValidationException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        commands: __DIR__ . '/../routes/console.php',
        channels: __DIR__ . '/../routes/channels.php',
        health: '/up',
        then: function () {
            Route::middleware('web')
                ->group(base_path('routes/landlord.php'));
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Without this, $request->ip() (used by fraud detection, rate limiting,
        // and audit logging) returns the load balancer/CDN's IP for every
        // visitor once this sits behind any reverse proxy — set TRUSTED_PROXIES
        // in .env to the proxy's real IP/CIDR range at deploy time (e.g.
        // Cloudflare's published ranges). Empty by default: trusts nothing,
        // so local/direct-connection behavior is unchanged until configured.
        $trustedProxies = array_filter(array_map('trim', explode(',', env('TRUSTED_PROXIES', ''))));
        if (!empty($trustedProxies)) {
            $middleware->trustProxies(
                at: $trustedProxies,
                headers: Request::HEADER_X_FORWARDED_FOR
                    | Request::HEADER_X_FORWARDED_HOST
                    | Request::HEADER_X_FORWARDED_PORT
                    | Request::HEADER_X_FORWARDED_PROTO
            );
        }

        $middleware->prepend(ForceHttps::class);
        $middleware->web(append: [
            HandleInertiaRequests::class,
        ]);

        // Exclude payment webhooks, API cart endpoints and chat API from CSRF verification
        $middleware->validateCsrfTokens(except: [
            '*/payment/webhook',
            '*/payment/webhook/*',
            '*/api/cart/track',
            '*/api/cart/convert',
            '*/chat/start',
            '*/chat/*/send',
            '*/chat/*/poll',
        ]);

        $middleware->alias([
            'auth.super_admin' => EnsureSuperAdmin::class,
            'auth.tenant' => EnsureTenantAuth::class,
            'auth.customer' => EnsureCustomerAuth::class,
            'role' => CheckRole::class,
            'permission' => CheckPermission::class,
            'check.setup' => CheckSetupComplete::class,
            'check.setup.pending' => EnsureInstallNotComplete::class,
            'check.license' => CheckTenantLicense::class,
            '2fa' => RequiresTwoFactor::class,
            'set.locale' => SetLocale::class,
            'set.currency' => SetCurrency::class,
            'plan.feature' => RequiresPlanFeature::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Zamień 429 na polską wiadomość — dla Inertia i JSON (axios)
        $exceptions->render(function (
            ThrottleRequestsException $e,
            Request $request
        ) {
            $message = __('messages.too_many_attempts');
            if ($request->inertia()) {
                throw ValidationException::withMessages([
                    'throttle' => $message,
                ]);
            }

            return response()->json(['message' => $message], 429);
        });
    })->create();
