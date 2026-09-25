<?php

namespace App\Http\Controllers\Tenant\Manager;

use App\Http\Controllers\Controller;
use App\Models\Tenant\User;
use App\Services\AuditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class ImpersonateController extends Controller
{
    /**
     * Handle incoming impersonation token from landlord.
     * Route: GET /manager/impersonate?token=...  (public, no auth required)
     */
    public function handle(Request $request)
    {
        $token = $request->query('token', '');

        if (!$token) {
            abort(403, __('messages.impersonation_token_invalid'));
        }

        // Use file store explicitly to bypass CacheTenancyBootstrapper (database store doesn't support tags)
        $data = Cache::store('file')->pull('impersonate:' . $token);

        if (!$data) {
            abort(403, 'Token impersonacji wygasł lub jest nieprawidłowy.');
        }

        if ((string) ($data['tenant_id'] ?? '') !== (string) tenant('id')) {
            abort(403, __('messages.impersonation_wrong_shop'));
        }

        $manager = User::find($data['user_id']);

        if (!$manager || $manager->role !== 'manager') {
            abort(403, 'Nie znaleziono konta managera.');
        }

        Auth::guard('tenant')->login($manager);
        $request->session()->regenerate();

        AuditService::log('tenant.impersonated', $manager, [], ['via' => 'landlord']);

        // Store impersonation flag in session so we can show "Stop impersonating" banner
        session(['impersonating' => true]);

        return redirect()->route('tenant.manager.dashboard');
    }

    /**
     * Stop impersonation and redirect to landlord panel.
     * Route: POST /manager/impersonate/stop
     */
    public function stop(Request $request)
    {
        Auth::guard('tenant')->logout();
        $request->session()->forget('impersonating');

        $scheme = app()->environment('production') ? 'https' : 'http';
        $baseDomain = config('tenancy.central_domains')[0] ?? config('app.base_domain', 'localhost');

        // Redirect to landlord admin panel
        return redirect()->away($scheme . '://admin.' . $baseDomain . '/admin/dashboard');
    }
}
