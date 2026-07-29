<?php

namespace App\Http\Middleware\Tenant;

use App\Models\Tenant\Setting;
use Closure;
use Illuminate\Http\Request;

class EnsureInstallComplete
{
    /** Paths that are always accessible (even when install not done) */
    private array $allowedPrefixes = [
        'install',
        'login',
        'logout',
        'forgot-password',
        'reset-password',
        'konto',
        'payment/webhook',
        'sitemap.xml',
        'robots.txt',
        '_debugbar',
    ];

    public function handle(Request $request, Closure $next)
    {
        // If setup is already done, always pass through
        try {
            if (Setting::get('setup_completed', false)) {
                return $next($request);
            }
        } catch (\Exception $e) {
            // DB not ready yet — let it through
            return $next($request);
        }

        // Authenticated tenant staff — let CheckSetupComplete handle the manager redirect
        if (auth('tenant')->check()) {
            return $next($request);
        }

        // Allow certain paths without setup
        foreach ($this->allowedPrefixes as $prefix) {
            if ($request->is($prefix) || $request->is($prefix . '/*')) {
                return $next($request);
            }
        }

        return redirect('/install');
    }
}
