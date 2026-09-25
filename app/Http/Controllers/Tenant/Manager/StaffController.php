<?php

namespace App\Http\Controllers\Tenant\Manager;

use App\Helpers\PhoneHelper;
use App\Http\Controllers\Controller;
use App\Models\Tenant\User;
use App\Services\AuditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class StaffController extends Controller
{
    public function index()
    {
        $staffList = User::latest()->paginate(15);

        return Inertia::render('Tenant/Manager/Staff/Index', [
            'staffList' => $staffList,
        ]);
    }

    public function create()
    {
        return Inertia::render('Tenant/Manager/Staff/Form');
    }

    public function store(Request $request)
    {
        // Plan::max_staff was never enforced anywhere. Same reasoning as
        // ProductController::store()'s max_products check — see
        // CheckoutController::store()'s max_orders_per_month check for why
        // tenancy()->tenant->plan is read directly rather than via
        // LicenseController's tenancy()->end()/initialize() dance.
        try {
            $limit = tenancy()->tenant?->plan?->max_staff;
            if ($limit !== null && User::count() >= $limit) {
                return back()->withErrors(['name' => __('messages.staff_limit_reached')]);
            }
        } catch (\Exception $e) {
            Log::warning('Plan staff-limit check failed, allowing creation: ' . $e->getMessage());
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8',
            'role' => 'required|in:manager,fulfillment,warehouse',
            'phone' => 'nullable|string|max:20',
        ]);

        $validated['password'] = Hash::make($validated['password']);
        $validated['phone'] = PhoneHelper::normalize($validated['phone'] ?? null);

        $user = User::create($validated);
        Log::info('Staff: pracownik dodany', ['user_id' => $user->id, 'email' => $user->email, 'role' => $user->role, 'manager_id' => Auth::guard('tenant')->id()]);
        // The single most privilege-sensitive action in the RBAC system
        // (granting a role) previously only went to the plain server log,
        // invisible in the manager-facing Audit Log panel.
        AuditService::log('staff.created', $user, [], ['email' => $user->email, 'role' => $user->role]);

        return redirect()->route('tenant.manager.staff.index')
            ->with('success', __('messages.staff_added'));
    }

    public function edit(User $staff)
    {
        return Inertia::render('Tenant/Manager/Staff/Form', [
            'user' => $staff,
        ]);
    }

    public function update(Request $request, User $staff)
    {
        $before = ['email' => $staff->email, 'role' => $staff->role, 'is_active' => $staff->is_active];

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $staff->id,
            'password' => 'nullable|string|min:8',
            'role' => 'required|in:manager,fulfillment,warehouse',
            'phone' => 'nullable|string|max:20',
            'is_active' => 'boolean',
        ]);

        // A manager demoting or deactivating their own account (accidental edit,
        // hijacked session) could lock everyone out of /manager — role:manager
        // gates the entire prefix and there's no in-tenant way to re-promote
        // anyone. Same protection destroy() already has for self-deletion.
        if ($staff->id === Auth::guard('tenant')->id()) {
            if ($validated['role'] !== 'manager') {
                return back()->withErrors(['role' => __('messages.staff_cannot_change_own_role')]);
            }
            if (array_key_exists('is_active', $validated) && !$validated['is_active']) {
                return back()->withErrors(['is_active' => __('messages.staff_cannot_deactivate_self')]);
            }
        }

        if (!empty($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        } else {
            unset($validated['password']);
        }
        $validated['phone'] = PhoneHelper::normalize($validated['phone'] ?? null);

        $staff->update($validated);
        Log::info('Staff: pracownik zaktualizowany', ['user_id' => $staff->id, 'email' => $staff->email, 'role' => $staff->role, 'manager_id' => Auth::guard('tenant')->id()]);
        AuditService::log('staff.updated', $staff, $before, [
            'email' => $staff->email, 'role' => $staff->role, 'is_active' => $staff->is_active,
        ]);

        return redirect()->route('tenant.manager.staff.index')
            ->with('success', __('messages.staff_updated'));
    }

    public function destroy(User $staff)
    {
        if ($staff->id === Auth::guard('tenant')->id()) {
            return back()->withErrors(['error' => __('messages.staff_cannot_delete_self')]);
        }

        Log::info('Staff: member removed', ['user_id' => $staff->id, 'email' => $staff->email, 'role' => $staff->role, 'manager_id' => Auth::guard('tenant')->id()]);
        AuditService::log('staff.deleted', $staff, ['email' => $staff->email, 'role' => $staff->role], []);
        $staff->delete();

        return back()->with('success', __('messages.staff_deleted'));
    }
}
