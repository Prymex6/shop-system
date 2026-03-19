<?php

namespace App\Http\Controllers\Tenant\Manager;

use App\Http\Controllers\Controller;
use App\Models\Tenant\RolePermission;
use App\Services\AuditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class RolePermissionsController extends Controller
{
    // Predefined permissions with labels
    public const PERMISSIONS = [
        'update_order_status' => 'Zmiana statusu zamówień',
        'accept_phone_orders' => 'Przyjmowanie zamówień telefonicznych',
        'view_cash' => 'Podgląd przychodów i kasy',
        'manage_returns' => 'Zarządzanie zwrotami i reklamacjami (RMA)',
        'process_refunds' => 'Wykonywanie zwrotów płatności',
        'manage_fraud' => 'Zarządzanie wykrywaniem oszustw (blokowanie, lista blokad)',
        'view_customers' => 'Podgląd danych klientów',
        'send_reports' => 'Wysyłanie raportów do managera',
    ];

    // Configurable roles
    public const ROLES = ['manager', 'fulfillment', 'warehouse'];

    // Default permissions per role (used during install/setup)
    public const DEFAULTS = [
        'fulfillment' => ['update_order_status', 'send_reports'],
        'warehouse' => ['send_reports'],
    ];

    public function index()
    {
        return Inertia::render('Tenant/Manager/RolePermissions', [
            'permissions' => self::PERMISSIONS,
            'roles' => self::ROLES,
            'currentPermissions' => RolePermission::allGrouped(),
        ]);
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['nullable', 'array'],
            'permissions.*.*' => ['boolean'],
        ]);

        $before = RolePermission::allGrouped();

        // Clear existing and rebuild inside a transaction to prevent data loss on failure
        DB::transaction(function () use ($validated) {
            RolePermission::whereIn('role', self::ROLES)->delete();

            foreach (self::ROLES as $role) {
                foreach (array_keys(self::PERMISSIONS) as $perm) {
                    if (!empty($validated['permissions'][$role][$perm])) {
                        RolePermission::create([
                            'role' => $role,
                            'permission' => $perm,
                        ]);
                    }
                }
            }
        });

        AuditService::log('role_permissions.updated', null, $before, RolePermission::allGrouped());

        return back()->with('success', __('messages.permissions_updated'));
    }
}
