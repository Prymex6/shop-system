<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureCustomerAuth
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!auth('customer')->check()) {
            return redirect()->route('tenant.client.login');
        }

        // See EnsureTenantAuth for why this can't just be Laravel's built-in
        // AuthenticateSession middleware (guard-locked to 'web') — same
        // password-hash-in-session check, scoped to the 'customer' guard.
        // Without it, resetting your password (e.g. because your account was
        // taken over) never actually logged out the attacker's own session.
        $user = auth('customer')->user();
        $sessionKey = 'password_hash_customer';
        $storedHash = $request->session()->get($sessionKey);

        if ($storedHash === null) {
            $request->session()->put($sessionKey, $user->getAuthPassword());
        } elseif (!hash_equals($user->getAuthPassword(), $storedHash)) {
            auth('customer')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('tenant.client.login')
                ->withErrors(['email' => __('messages.password_changed_sign_in_again')]);
        }

        $response = $next($request);

        if (auth('customer')->check()) {
            $request->session()->put($sessionKey, auth('customer')->user()->getAuthPassword());
        }

        return $response;
    }
}
