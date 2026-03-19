<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTenantAuth
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!auth('tenant')->check()) {
            return redirect()->route('tenant.login');
        }

        $user = auth('tenant')->user();

        if (!$user->is_active) {
            auth('tenant')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('tenant.login')
                ->withErrors(['email' => 'Twoje konto zostało dezaktywowane.']);
        }

        // Password changed elsewhere (email reset link, or the self-service
        // "change password" form from another session) never used to
        // invalidate this session — a stolen or forgotten-open session
        // stayed valid indefinitely even after the real owner reset their
        // password specifically to lock an intruder out. Laravel's built-in
        // AuthenticateSession middleware can't be used here because it only
        // ever checks config('auth.defaults.guard') ('web'), never a named
        // guard like 'tenant' — so this mirrors its password-hash-in-session
        // approach directly, scoped to the guard this middleware protects.
        $sessionKey = 'password_hash_tenant';
        $storedHash = $request->session()->get($sessionKey);

        if ($storedHash === null) {
            $request->session()->put($sessionKey, $user->getAuthPassword());
        } elseif (!hash_equals($user->getAuthPassword(), $storedHash)) {
            auth('tenant')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('tenant.login')
                ->withErrors(['email' => 'Twoje hasło zostało zmienione. Zaloguj się ponownie.']);
        }

        $response = $next($request);

        // Re-sync after the request in case this very request was the
        // password change — keeps the session that made the change alive
        // instead of logging its own owner out on their next request.
        if (auth('tenant')->check()) {
            $request->session()->put($sessionKey, auth('tenant')->user()->getAuthPassword());
        }

        return $response;
    }
}
