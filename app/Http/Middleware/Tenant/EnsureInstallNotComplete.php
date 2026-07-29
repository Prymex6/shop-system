<?php

namespace App\Http\Middleware\Tenant;

use App\Models\Tenant\Setting;
use Closure;
use Illuminate\Http\Request;

/**
 * Blocks every /install/* route once setup_completed is true. The install
 * wizard is deliberately unauthenticated (there's no manager account yet on
 * a fresh tenant), so once a store is live this is the only thing stopping
 * an anonymous visitor from re-running it — e.g. POST /install/shop would
 * silently overwrite the store's public shop_name/shop_email/shop_phone/
 * shop_nip. Applied once at the route-group level so a future new install
 * step can't reintroduce this gap by forgetting a per-method check.
 */
class EnsureInstallNotComplete
{
    public function handle(Request $request, Closure $next)
    {
        if (Setting::get('setup_completed', false)) {
            return redirect('/');
        }

        return $next($request);
    }
}
