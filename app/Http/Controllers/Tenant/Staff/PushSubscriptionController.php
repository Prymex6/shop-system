<?php

namespace App\Http\Controllers\Tenant\Staff;

use App\Http\Controllers\Controller;
use App\Models\Tenant\PushSubscription;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PushSubscriptionController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'endpoint' => ['required', 'string'],
            'p256dh_key' => ['nullable', 'string'],
            'auth_key' => ['nullable', 'string'],
            'expires_at' => ['nullable', 'string'],
        ]);

        $staffUserId = Auth::guard('tenant')->id();

        // Matched on endpoint alone before — a staff member who sent
        // someone else's endpoint (plus their own keys) would silently
        // rewrite that subscription's staff_user_id to themselves,
        // hijacking the original owner's notification slot. Matching on
        // (endpoint, staff_user_id) means a different caller can never
        // touch another owner's row; destroy() already scoped this way.
        PushSubscription::updateOrCreate(
            ['endpoint' => $validated['endpoint'], 'staff_user_id' => $staffUserId],
            [
                'p256dh_key' => $validated['p256dh_key'] ?? null,
                'auth_key' => $validated['auth_key'] ?? null,
                'expires_at' => isset($validated['expires_at']) ? date('Y-m-d H:i:s', $validated['expires_at'] / 1000) : null,
            ]
        );

        return response()->json(['success' => true]);
    }

    public function destroy(Request $request)
    {
        $endpoint = $request->input('endpoint');
        if ($endpoint) {
            PushSubscription::where('endpoint', $endpoint)
                ->where('staff_user_id', Auth::guard('tenant')->id())
                ->delete();
        }

        return response()->json(['success' => true]);
    }
}
