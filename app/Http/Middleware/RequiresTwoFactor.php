<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequiresTwoFactor
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = auth('tenant')->user();

        if (!$user) {
            return $next($request);
        }

        // If 2FA is enabled for this user and not yet verified in this session
        if ($user->two_factor_enabled && !$request->session()->get('2fa_verified', false)) {
            // Store user ID for 2FA verification, log them out temporarily
            $request->session()->put('2fa_user_id', $user->id);
            auth('tenant')->logout();

            return redirect()->route('tenant.2fa.verify');
        }

        return $next($request);
    }
}
