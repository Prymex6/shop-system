<?php

namespace App\Http\Middleware;

use App\Models\Tenant\Setting;
use App\Services\CurrencyService;
use Closure;
use Illuminate\Http\Request;

class SetCurrency
{
    public function __construct(protected CurrencyService $currencyService) {}

    public function handle(Request $request, Closure $next)
    {
        try {
            $enabledRaw = Setting::get('enabled_currencies', ['PLN']);
            $enabled = is_array($enabledRaw) ? $enabledRaw : (json_decode($enabledRaw, true) ?? ['PLN']);

            // Priority: ?currency= param → session → default
            $currency = $request->query('currency')
                ?? $request->session()->get('currency')
                ?? Setting::get('currency', 'PLN');

            if (!in_array($currency, $this->currencyService->getSupportedCurrencies())) {
                $currency = $enabled[0] ?? 'PLN';
            }

            if (!in_array($currency, $enabled)) {
                $currency = $enabled[0] ?? 'PLN';
            }

            $request->session()->put('currency', $currency);
            config(['shop.currency' => $currency]);
        } catch (\Exception) {
            config(['shop.currency' => 'PLN']);
        }

        return $next($request);
    }
}
