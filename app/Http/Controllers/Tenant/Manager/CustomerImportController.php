<?php

namespace App\Http\Controllers\Tenant\Manager;

use App\Http\Controllers\Controller;
use App\Jobs\ImportCustomersCsv;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CustomerImportController extends Controller
{
    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,txt|max:5120',
        ]);

        // Explicit 'local' (private) disk — contains customer PII (names,
        // emails, addresses). Previously used the app's default disk,
        // which is FILESYSTEM_DISK=public, landing the CSV under the
        // publicly-servable storage/app/public/ path until the import job
        // cleaned it up.
        $path = $request->file('file')->store('imports/customers', 'local');

        ImportCustomersCsv::dispatch(
            $path,
            tenancy()->tenant->id,
            Auth::id()
        );

        return back()->with('success', __('messages.customer_import_started'));
    }
}
