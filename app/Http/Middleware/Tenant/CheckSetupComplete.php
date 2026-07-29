<?php

namespace App\Http\Middleware\Tenant;

use App\Models\Tenant\Setting;
use Closure;
use Illuminate\Http\Request;

class CheckSetupComplete
{
    public function handle(Request $request, Closure $next)
    {
        // Only applies to manager routes
        if (!str_starts_with($request->path(), 'manager')) {
            return $next($request);
        }

        // Skip for setup routes themselves
        if (str_starts_with($request->path(), 'manager/setup')) {
            return $next($request);
        }

        // Check if setup completed
        if (!Setting::get('setup_completed', false)) {
            return redirect('/manager/setup');
        }

        return $next($request);
    }
}
