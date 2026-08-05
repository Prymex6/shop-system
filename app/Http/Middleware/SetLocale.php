<?php

namespace App\Http\Middleware;

use App\Models\Tenant\Setting;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Serve the shop in the language it is configured for.
 *
 * A visitor may ask for one of the other languages the shop offers, and that
 * choice is remembered in the session for as long as they are browsing. It is
 * never taken from the Accept-Language header: which languages a shop sells
 * in is the owner's decision, not the browser's.
 */
class SetLocale
{
    public const SESSION_KEY = 'locale';

    public function handle(Request $request, Closure $next): Response
    {
        $available = $this->availableLocales();
        $chosen = $request->session()->get(self::SESSION_KEY);

        $locale = in_array($chosen, $available, true)
            ? $chosen
            : ($available[0] ?? config('app.fallback_locale'));

        app()->setLocale($locale);

        return $next($request);
    }

    /**
     * @return list<string>
     */
    private function availableLocales(): array
    {
        try {
            $shopDefault = Setting::get('shop_language', 'pl');
            $available = Setting::get('available_languages', [$shopDefault]);
        } catch (\Throwable) {
            // No tenant database yet (install wizard, central domain).
            return [config('app.fallback_locale')];
        }

        if (is_string($available)) {
            $available = json_decode($available, true) ?: [$shopDefault];
        }

        $supported = config('app.supported_locales', ['pl']);

        // The shop's own language comes first, so it is what a visitor who has
        // not chosen anything is served.
        $ordered = array_values(array_unique(array_merge([$shopDefault], (array) $available)));

        $ordered = array_values(array_intersect($ordered, $supported));

        return $ordered ?: [config('app.fallback_locale')];
    }
}
