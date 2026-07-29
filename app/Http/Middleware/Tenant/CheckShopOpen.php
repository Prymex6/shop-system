<?php

namespace App\Http\Middleware\Tenant;

use App\Models\Tenant\Setting;
use Closure;
use Illuminate\Http\Request;

class CheckShopOpen
{
    public function handle(Request $request, Closure $next)
    {
        // Manual orders pause
        if (Setting::get('orders_paused', false)) {
            return response()->json(['message' => __('messages.orders_paused')], 422);
        }

        // Vacation / maintenance mode
        if (Setting::get('vacation_mode', false)) {
            $message = Setting::get('vacation_message', 'Sklep jest chwilowo niedostępny. Zapraszamy później.');

            return response()->json(['message' => $message], 422);
        }

        return $next($request);
    }
}
