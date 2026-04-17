<?php

namespace App\Http\Controllers\Tenant\Manager;

use App\Http\Controllers\Controller;
use App\Jobs\ImportOrdersCsv;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class OrderImportController extends Controller
{
    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,txt|max:10240',
        ]);

        // Explicit 'local' (private) disk — see CustomerImportController for why.
        $path = $request->file('file')->store('imports/orders', 'local');

        ImportOrdersCsv::dispatch(
            $path,
            tenancy()->tenant->id,
            Auth::id()
        );

        return back()->with('success', __('messages.order_import_started'));
    }
}
