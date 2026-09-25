<?php

namespace App\Http\Controllers\Tenant\Manager;

use App\Http\Controllers\Controller;
use App\Models\Tenant\User;
use App\Services\AuditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class HubImpersonateController extends Controller
{
    /**
     * Consume a cross-app impersonation token created by the hub's admin
     * panel. The token lives in the central database (written directly by
     * the hub via its own DB connection) since the hub and this app are
     * separate Laravel installs with separate cache stores.
     * Route: GET /hub-impersonate/{token}  (tenant domain, no auth required)
     */
    public function handle(Request $request, string $token)
    {
        if (!DB::connection('central')->getSchemaBuilder()->hasTable('hub_impersonation_tokens')) {
            abort(403, __('messages.impersonation_no_tokens'));
        }

        $row = DB::connection('central')->table('hub_impersonation_tokens')
            ->where('token', $token)
            ->first();

        if (!$row || now()->greaterThan($row->expires_at)) {
            abort(403, __('messages.impersonation_token_expired'));
        }

        if ((string) $row->tenant_id !== (string) tenant('id')) {
            abort(403, __('messages.impersonation_wrong_tenant'));
        }

        DB::connection('central')->table('hub_impersonation_tokens')->where('token', $token)->delete();

        $user = User::find($row->user_id);

        if (!$user) {
            abort(403, __('messages.user_not_found'));
        }

        Auth::guard('tenant')->login($user);
        $request->session()->regenerate();

        AuditService::log('tenant.impersonated', $user, [], ['via' => 'hub']);

        return redirect()->route('tenant.manager.dashboard');
    }
}
