<?php

namespace App\Http\Controllers\Tenant\Staff;

use App\Http\Controllers\Controller;
use App\Models\Tenant\Setting;
use Illuminate\Http\Request;

class QuickControlsController extends Controller
{
    public function update(Request $request)
    {
        $data = $request->validate([
            'orders_paused' => 'sometimes|boolean',
        ]);

        foreach ($data as $key => $value) {
            Setting::set($key, $value, 'boolean');
        }

        return response()->json([
            'orders_paused' => filter_var(Setting::get('orders_paused', false), FILTER_VALIDATE_BOOLEAN),
        ]);
    }
}
