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
    /**
     * Every permission a role can be given.
     *
     * The labels are not here: a constant cannot call __(), and these are
     * read off the screen by whoever is setting the roles up.
     */
    public const PERMISSIONS = [
        'update_order_status',
        'accept_phone_orders',
        'view_cash',
        'manage_returns',
        'process_refunds',
        'manage_fraud',
        'view_customers',
        'send_reports',
    ];

    /**
     * @return array<string, string> permission => what it says on the screen
     */
    public static function permissionLabels(): array
    {
        $labels = [];

        foreach (self::PERMISSIONS as $permission) {
            $labels[$permission] = __("messages.permission_{$permission}");
        }

        return $labels;
    }

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
            'permissions' => self::permissionLabels(),
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
                foreach (self::PERMISSIONS as $perm) {
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
