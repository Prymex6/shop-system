<?php

namespace App\Http\Controllers\Tenant\Client;

use App\Helpers\PhoneHelper;
use App\Http\Controllers\Controller;
use App\Mail\Tenant\CustomerWelcomeMail;
use App\Models\Tenant\Customer;
use App\Models\Tenant\Setting;
use App\Services\LoyaltyService;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class CustomerAuthController extends Controller
{
    public function showLogin()
    {
        if (Auth::guard('customer')->check()) {
            return redirect()->route('tenant.account');
        }

        return Inertia::render('Tenant/Client/Auth/Login');
    }

    public function showRegister()
    {
        if (Auth::guard('customer')->check()) {
            return redirect()->route('tenant.account');
        }

        return Inertia::render('Tenant/Client/Auth/Register');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (Auth::guard('customer')->attempt($credentials, $request->boolean('remember'))) {
            $request->session()->regenerate();
            Log::info('Auth[customer]: zalogowano', ['email' => $credentials['email'], 'ip' => $request->ip()]);

            return redirect()->intended(route('tenant.shop'));
        }

        Log::warning('Auth[customer]: nieudane logowanie', ['email' => $credentials['email'], 'ip' => $request->ip()]);

        throw ValidationException::withMessages([
            'email' => __('messages.credentials_wrong'),
        ]);
    }

    public function register(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:customers,email',
            'password' => 'required|string|min:8|confirmed',
            'phone' => 'nullable|string|max:30',
            'referral_code' => 'nullable|string|max:10',
            'terms_accepted' => 'required|accepted',
        ]);

        // Resolve referrer
        $referrer = null;
        if (!empty($validated['referral_code'])) {
            $referrer = Customer::where('referral_code', strtoupper($validated['referral_code']))->first();
        }

        $customer = Customer::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => $validated['password'],
            'phone' => PhoneHelper::normalize($validated['phone'] ?? null),
            'loyalty_referred_by' => $referrer?->id,
            'registration_ip' => $request->ip(),
        ]);

        Auth::guard('customer')->login($customer, true);
        $request->session()->regenerate();

        Log::info('Auth[customer]: nowe konto', [
            'customer_id' => $customer->id,
            'email' => $customer->email,
            'referrer_id' => $referrer?->id,
            'ip' => $request->ip(),
        ]);

        app(LoyaltyService::class)->awardRegistrationBonus($customer);

        try {
            Mail::to($customer->email)
                ->queue(new CustomerWelcomeMail(
                    customerName: $customer->name,
                    shopName: Setting::get('shop_name') ?: config('app.name'),
                ));
        } catch (\Throwable $e) {
            Log::warning('Welcome email failed: ' . $e->getMessage());
        }

        return redirect()->route('tenant.shop')
            ->with('success', __('messages.account_created'));
    }

    public function logout(Request $request)
    {
        $email = Auth::guard('customer')->user()?->email;
        Auth::guard('customer')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        Log::info('Auth[customer]: wylogowano', ['email' => $email, 'ip' => $request->ip()]);

        return redirect()->route('tenant.shop');
    }

    public function showForgotPassword()
    {
        if (Auth::guard('customer')->check()) {
            return redirect()->route('tenant.account');
        }

        return Inertia::render('Tenant/Client/Auth/ForgotPassword');
    }

    public function sendResetLink(Request $request)
    {
        $request->validate(['email' => 'required|email']);

        try {
            $status = Password::broker('customers')->sendResetLink(
                $request->only('email')
            );
        } catch (\Exception $e) {
            Log::warning('Password reset email failed: ' . $e->getMessage());
            $status = Password::RESET_LINK_SENT;
        }

        Log::info('Auth[customer]: password reset requested', ['email' => $request->email, 'status' => $status]);

        // Always the same response regardless of whether the email exists,
        // was throttled, or actually got sent — INVALID_USER previously
        // surfaced as a distinct form validation error ("No such user"),
        // letting an attacker enumerate which emails have an account here
        // just by reading the response, no timing needed.
        return back()->with('success', __('messages.password_reset_sent'));
    }

    public function showResetPassword(Request $request, string $token)
    {
        $email = $request->email;
        $tokenValid = false;
        if ($email) {
            $customer = Customer::where('email', $email)->first();
            if ($customer) {
                $tokenValid = Password::broker('customers')->tokenExists($customer, $token);
            }
        }

        return Inertia::render('Tenant/Client/Auth/ResetPassword', [
            'token' => $token,
            'email' => $email,
            'tokenValid' => $tokenValid,
        ]);
    }

    public function resetPassword(Request $request)
    {
        $request->validate([
            'token' => 'required',
            'email' => 'required|email',
            'password' => 'required|min:8|confirmed',
        ]);

        $status = Password::broker('customers')->reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, $password) {
                $user->forceFill(['password' => Hash::make($password)])->save();
                event(new PasswordReset($user));

                // "My account was compromised, resetting my password" is exactly
                // the scenario a stale session on another device/browser defeats
                // if it isn't also invalidated. logoutOtherDevices() itself only
                // force-rehashes the password; the actual invalidation of other
                // sessions happens in EnsureCustomerAuth, which compares each
                // request's stored session password hash against the current
                // one and logs out any session that's now stale.
                Auth::guard('customer')->logoutOtherDevices($password);
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            Log::info('Auth[customer]: password reset', ['email' => $request->email]);

            return redirect()->route('tenant.client.login')
                ->with('status', __('messages.password_changed_can_sign_in'));
        }

        return back()->withErrors(['email' => __($status)]);
    }
}
