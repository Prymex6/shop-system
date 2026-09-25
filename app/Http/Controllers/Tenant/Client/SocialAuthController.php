<?php

namespace App\Http\Controllers\Tenant\Client;

use App\Http\Controllers\Controller;
use App\Models\Tenant\Customer;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Laravel\Socialite\Facades\Socialite;

class SocialAuthController extends Controller
{
    public function redirect(string $provider)
    {
        $this->validateProvider($provider);

        return Socialite::driver($provider)->redirect();
    }

    public function callback(string $provider)
    {
        $this->validateProvider($provider);

        try {
            $socialUser = Socialite::driver($provider)->user();
        } catch (\Exception $e) {
            Log::warning('Auth[social]: sign-in failed ' . $provider, ['error' => $e->getMessage(), 'ip' => request()->ip()]);

            return redirect()->route('tenant.client.login')
                ->with('error', __('messages.social_login_failed', ['provider' => ucfirst($provider)]));
        }

        $providerIdField = $provider . '_id';

        // Match by provider ID first (this IS the same person — they've logged
        // in with this provider before). Only fall back to an email match when
        // that email has no password set yet — i.e. the account itself was
        // created via a provider, not a password registration — so we're not
        // silently taking over a password-protected account just because an
        // OAuth response happened to carry the same email address.
        $customer = Customer::where($providerIdField, $socialUser->getId())->first();

        if (!$customer) {
            $emailMatch = Customer::where('email', $socialUser->getEmail())->first();
            if ($emailMatch && $emailMatch->password) {
                Log::warning('Auth[social]: email matches an existing password account — refusing silent link', [
                    'email' => $socialUser->getEmail(),
                    'provider' => $provider,
                    'ip' => request()->ip(),
                ]);

                return redirect()->route('tenant.client.login')
                    ->with('error', __('messages.social_email_taken', ['provider' => ucfirst($provider)]));
            }
            $customer = $emailMatch;
        }

        $isNew = false;
        if ($customer) {
            // Update provider ID if not set
            if (!$customer->$providerIdField) {
                $customer->update([$providerIdField => $socialUser->getId()]);
            }
            if ($socialUser->getAvatar() && !$customer->avatar) {
                $customer->update(['avatar' => $socialUser->getAvatar()]);
            }
        } else {
            // Create new customer
            $customer = Customer::create([
                'name' => $socialUser->getName() ?? $socialUser->getNickname() ?? __('messages.social_default_name'),
                'email' => $socialUser->getEmail(),
                $providerIdField => $socialUser->getId(),
                'avatar' => $socialUser->getAvatar(),
            ]);
            $isNew = true;
        }

        Auth::guard('customer')->login($customer, true);
        request()->session()->regenerate();

        Log::info('Auth[social]: zalogowano przez ' . $provider, [
            'customer_id' => $customer->id,
            'email' => $customer->email,
            'new_account' => $isNew,
            'ip' => request()->ip(),
        ]);

        return redirect()->route('tenant.shop')
            ->with('success', 'Zalogowano przez ' . ucfirst($provider) . '!');
    }

    protected function validateProvider(string $provider): void
    {
        if (!in_array($provider, ['google', 'facebook'])) {
            abort(404);
        }

        if ((tenancy()->tenant?->version ?? 'stable') !== 'test') {
            abort(403, __('messages.social_login_demo_only'));
        }
    }
}
