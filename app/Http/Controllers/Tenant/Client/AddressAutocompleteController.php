<?php

namespace App\Http\Controllers\Tenant\Client;

use App\Http\Controllers\Controller;
use App\Services\AddressAutocompleteService;
use Illuminate\Http\Request;

class AddressAutocompleteController extends Controller
{
    public function __construct(protected AddressAutocompleteService $service) {}

    public function suggest(Request $request)
    {
        $request->validate([
            'q' => 'required|string|min:2|max:100',
        ]);

        $suggestions = $this->service->suggest($request->q);

        return response()->json($suggestions);
    }
}
