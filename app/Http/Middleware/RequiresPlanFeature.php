<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

/**
 * Plan::features is a freeform JSON flag list a landlord types into the
 * "Funkcje" form — LandlordSeeder shows the concrete keys real plans are
 * seeded with (analytics, loyalty_program, digital_products, ...), but
 * nothing tenant-side ever checked any of them before this: every plan's
 * tenant had access to every gated feature regardless of what they pay for.
 * A lookup failure never blocks access — a plan-check bug locking a real
 * tenant out of a page they're paying for is worse than one page staying
 * reachable for a plan that technically shouldn't have it.
 */
class RequiresPlanFeature
{
    public function handle(Request $request, Closure $next, string $feature): Response
    {
        try {
            $plan = tenancy()->tenant?->plan;
            if ($plan && !$plan->hasFeature($feature)) {
                abort(403, 'Ta funkcja nie jest dostępna w Twoim planie. Skontaktuj się z obsługą, aby zmienić plan.');
            }
        } catch (HttpExceptionInterface $e) {
            throw $e;
        } catch (\Exception $e) {
            Log::warning("Plan feature check failed for '{$feature}', allowing access: " . $e->getMessage());
        }

        return $next($request);
    }
}
