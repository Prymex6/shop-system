<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Stancl\Tenancy\Database\Models\Domain;
use Symfony\Component\HttpFoundation\Response;

class PreventAccessFromTenantDomains
{
    /**
     * Handle an incoming request.
     *
     * Prevent access to landlord routes from tenant domains.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $domain = $request->getHost();

        // Check if this domain belongs to a tenant
        $isTenantDomain = Domain::where('domain', $domain)->exists();

        if ($isTenantDomain) {
            // This is a tenant domain, don't allow access to landlord routes
            abort(404);
        }

        return $next($request);
    }
}
