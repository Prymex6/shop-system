<?php

namespace App\Http\Middleware;

use App\Models\Tenant\RolePermission;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckPermission
{
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        $user = auth('tenant')->user();

        if (!$user) {
            return redirect()->route('tenant.login');
        }

        // Manager always has all permissions
        if ($user->role === 'manager') {
            return $next($request);
        }

        if (!RolePermission::roleHas($user->role, $permission)) {
            if ($request->wantsJson() || $request->inertia()) {
                return response()->json(['message' => __('messages.forbidden')], 403);
            }

            return redirect()->route('tenant.staff.redirect')
                ->with('error', __('messages.no_permission_action'));
        }

        return $next($request);
    }
}
