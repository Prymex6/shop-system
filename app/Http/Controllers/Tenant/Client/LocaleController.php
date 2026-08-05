<?php

namespace App\Http\Controllers\Tenant\Client;

use App\Http\Controllers\Controller;
use App\Http\Middleware\SetLocale;
use App\Models\Tenant\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Let a visitor read the shop in another language it is offered in.
 *
 * The choice lives in the session, not on the customer record: someone who
 * has not signed in gets the same courtesy as someone who has.
 */
class LocaleController extends Controller
{
    public function set(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'locale' => ['required', 'string', 'in:' . implode(',', $this->offered())],
        ]);

        $request->session()->put(SetLocale::SESSION_KEY, $validated['locale']);

        return back();
    }

    /**
     * @return list<string>
     */
    private function offered(): array
    {
        $available = Setting::get('available_languages', [Setting::get('shop_language', 'pl')]);

        if (is_string($available)) {
            $available = json_decode($available, true) ?: ['pl'];
        }

        return array_values(array_intersect(
            (array) $available,
            config('app.supported_locales', ['pl']),
        ));
    }
}
