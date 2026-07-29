<?php

namespace App\Http\Middleware\Tenant;

use Closure;
use Illuminate\Http\Request;

class CheckTenantLicense
{
    public function handle(Request $request, Closure $next)
    {
        $tenant = tenancy()->tenant;

        if (!$tenant) {
            return $next($request);
        }

        if ($tenant->license_ends_at && $tenant->license_ends_at->isPast()) {
            return response(view('errors.license-expired', ['tenant' => $tenant]), 402);
        }

        // Also block suspended tenants on public routes
        if ($tenant->status === 'suspended') {
            return response(view('errors.license-expired', ['tenant' => $tenant, 'suspended' => true]), 402);
        }

        if ($tenant->status === 'pending_deletion') {
            return response(view('errors.license-expired', ['tenant' => $tenant, 'suspended' => true]), 402);
        }

        return $next($request);
    }
}
