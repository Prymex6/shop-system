<?php

namespace App\Http\Controllers\Landlord;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class AuthController extends Controller
{
    public function showLogin()
    {
        return Inertia::render('Landlord/Auth/Login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (Auth::guard('super_admin')->attempt($credentials, $request->boolean('remember'))) {
            $request->session()->regenerate();
            Log::info('Auth[super_admin]: zalogowano', ['email' => $credentials['email'], 'ip' => $request->ip()]);

            return redirect()->intended(route('landlord.dashboard'));
        }

        Log::warning('Auth[super_admin]: nieudane logowanie', ['email' => $credentials['email'], 'ip' => $request->ip()]);

        return back()->withErrors([
            'email' => 'Podane dane logowania są nieprawidłowe.',
        ])->onlyInput('email');
    }

    public function logout(Request $request)
    {
        $email = Auth::guard('super_admin')->user()?->email;
        Auth::guard('super_admin')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        Log::info('Auth[super_admin]: wylogowano', ['email' => $email, 'ip' => $request->ip()]);

        return redirect()->route('landlord.login');
    }
}
