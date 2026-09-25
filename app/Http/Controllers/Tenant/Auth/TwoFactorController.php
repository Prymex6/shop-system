<?php

namespace App\Http\Controllers\Tenant\Auth;

use App\Http\Controllers\Controller;
use App\Models\Tenant\Setting;
use App\Models\Tenant\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Inertia\Inertia;

class TwoFactorController extends Controller
{
    /**
     * Show the 2FA setup page.
     * GET /staff/2fa/enable
     */
    public function showEnable()
    {
        $user = Auth::guard('tenant')->user();

        return Inertia::render('Tenant/Auth/TwoFactor/Enable', [
            'two_factor_enabled' => (bool) $user->two_factor_enabled,
            'recovery_codes_count' => count($user->two_factor_recovery_codes ?? []),
        ]);
    }

    /**
     * Enable 2FA for the current user.
     * POST /staff/2fa/enable
     */
    public function enable(Request $request)
    {
        $request->validate([
            'password' => ['required', 'current_password:tenant'],
        ]);

        $user = Auth::guard('tenant')->user();

        $recoveryCodes = collect(range(1, 8))->map(fn () => strtoupper(Str::random(10)));

        $user->update([
            'two_factor_enabled' => true,
            'two_factor_recovery_codes' => $recoveryCodes->map(fn ($c) => Hash::make($c))->toArray(),
        ]);

        return back()->with('success', __('messages.two_factor_enabled'))->with('recovery_codes', $recoveryCodes->toArray());
    }

    /**
     * Disable 2FA for the current user.
     * POST /staff/2fa/disable
     */
    public function disable(Request $request)
    {
        $request->validate([
            'password' => ['required', 'current_password:tenant'],
        ]);

        $user = Auth::guard('tenant')->user();
        $user->update([
            'two_factor_enabled' => false,
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
            'two_factor_code' => null,
            'two_factor_code_expires_at' => null,
        ]);

        return back()->with('success', __('messages.two_factor_disabled'));
    }

    /**
     * Show the 2FA verification page (after login).
     * GET /staff/2fa/verify
     */
    public function showVerify(Request $request)
    {
        if (!$request->session()->has('2fa_user_id')) {
            return redirect()->route('tenant.login');
        }

        return Inertia::render('Tenant/Auth/TwoFactor/Verify');
    }

    /**
     * Send 2FA code via email.
     * POST /staff/2fa/send-code
     */
    public function sendCode(Request $request)
    {
        $userId = $request->session()->get('2fa_user_id');

        if (!$userId) {
            return response()->json(['message' => __('messages.session_expired')], 403);
        }

        $user = User::find($userId);

        if (!$user) {
            return response()->json(['message' => __('messages.user_not_found')], 403);
        }

        $code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $expires = now()->addMinutes(10);

        $user->update([
            'two_factor_code' => $code,
            'two_factor_code_expires_at' => $expires,
        ]);

        try {
            $shopName = Setting::get('shop_name', config('app.name'));
            Mail::raw(
                __('messages.two_factor_code_body', ['code' => $code, 'shop' => $shopName]),
                function ($message) use ($user, $shopName) {
                    $message->to($user->email)->subject(__('messages.two_factor_code_subject', ['shop' => $shopName]));
                }
            );
        } catch (\Throwable $e) {
            Log::error('TwoFactor: failed to send code email', ['error' => $e->getMessage()]);
        }

        return response()->json(['sent' => true, 'message' => __('messages.verification_code_sent')]);
    }

    /**
     * Verify the 2FA code.
     * POST /staff/2fa/verify
     */
    public function verify(Request $request)
    {
        $request->validate([
            'code' => ['required', 'string'],
        ]);

        $userId = $request->session()->get('2fa_user_id');

        if (!$userId) {
            return back()->withErrors(['code' => __('messages.session_expired_sign_in')]);
        }

        $user = User::find($userId);

        if (!$user) {
            return back()->withErrors(['code' => __('messages.user_not_found')]);
        }

        $inputCode = trim($request->code);

        // Check recovery code (hashed at rest)
        $recoveryCodes = $user->two_factor_recovery_codes ?? [];
        foreach ($recoveryCodes as $index => $hashedCode) {
            if (Hash::check($inputCode, $hashedCode)) {
                $remaining = collect($recoveryCodes)->forget($index)->values()->all();
                $user->update([
                    'two_factor_recovery_codes' => $remaining,
                    'two_factor_code' => null,
                    'two_factor_code_expires_at' => null,
                ]);

                $this->completeLogin($request, $user);

                return redirect()->route('tenant.staff.redirect');
            }
        }

        // Check TOTP code (email-based)
        if (!$user->two_factor_code ||
            !$user->two_factor_code_expires_at ||
            $user->two_factor_code_expires_at->isPast() ||
            !hash_equals((string) $user->two_factor_code, $inputCode)
        ) {
            return back()->withErrors(['code' => __('messages.two_factor_code_invalid')]);
        }

        // Valid code
        $user->update([
            'two_factor_code' => null,
            'two_factor_code_expires_at' => null,
        ]);

        $this->completeLogin($request, $user);

        return redirect()->route('tenant.staff.redirect');
    }

    /**
     * Generate new recovery codes.
     * POST /staff/2fa/recovery-codes
     */
    public function generateRecoveryCodes(Request $request)
    {
        $request->validate([
            'password' => ['required', 'current_password:tenant'],
        ]);

        $user = Auth::guard('tenant')->user();
        $codes = collect(range(1, 8))->map(fn () => strtoupper(Str::random(10)));

        $user->update(['two_factor_recovery_codes' => $codes->map(fn ($c) => Hash::make($c))->toArray()]);

        return back()->with('success', 'Kody odzyskiwania wygenerowane.')->with('recovery_codes', $codes->toArray());
    }

    protected function completeLogin(Request $request, $user): void
    {
        Auth::guard('tenant')->login($user);
        $request->session()->forget('2fa_user_id');
        $request->session()->put('2fa_verified', true);
        $request->session()->regenerate();
    }
}
