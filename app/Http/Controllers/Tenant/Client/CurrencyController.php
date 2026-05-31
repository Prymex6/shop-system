<?php

namespace App\Http\Controllers\Tenant\Client;

use App\Http\Controllers\Controller;
use App\Services\CurrencyService;
use Illuminate\Http\Request;

class CurrencyController extends Controller
{
    public function __construct(protected CurrencyService $currencyService) {}

    public function set(Request $request)
    {
        $request->validate([
            'currency' => 'required|string|in:' . implode(',', $this->currencyService->getSupportedCurrencies()),
        ]);

        $request->session()->put('currency', $request->currency);

        return back();
    }
}
