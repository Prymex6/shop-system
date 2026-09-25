<?php

namespace App\Http\Controllers\Landlord;

use App\Http\Controllers\Controller;
use App\Models\Landlord\Tenant;
use App\Models\Landlord\TenantModification;
use App\Services\TenantModificationService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ModificationController extends Controller
{
    public function index()
    {
        $modifications = TenantModification::on('central')
            ->latest()
            ->get()
            ->map(function ($mod) {
                $patchCount = count($mod->getPatches());
                $tenantCount = $mod->tenant_ids === null ? 'wszystkie' : count($mod->tenant_ids ?? []);

                return [
                    'id' => $mod->id,
                    'name' => $mod->name,
                    'code' => $mod->code,
                    'description' => $mod->description,
                    'version' => $mod->version,
                    'author' => $mod->author,
                    'status' => $mod->status,
                    'tenant_count' => $tenantCount,
                    'patch_count' => $patchCount,
                    'created_at' => $mod->created_at,
                ];
            });

        $tenants = Tenant::on('central')
            ->select('id', 'name', 'status')
            ->orderBy('name')
            ->get();

        return Inertia::render('Landlord/Modifications/Index', [
            'modifications' => $modifications,
            'tenants' => $tenants,
        ]);
    }

    public function create()
    {
        $tenants = Tenant::on('central')
            ->select('id', 'name', 'status')
            ->orderBy('name')
            ->get();

        return Inertia::render('Landlord/Modifications/Form', [
            'modification' => null,
            'tenants' => $tenants,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:100|unique:central.tenant_modifications,code|alpha_dash',
            'description' => 'nullable|string|max:2000',
            'version' => 'nullable|string|max:20',
            'author' => 'nullable|string|max:100',
            'status' => 'boolean',
            'tenant_ids' => 'nullable|array',
            'tenant_ids.*' => 'string',
            'rules_json' => 'nullable|string',
        ]);

        $rules = null;
        if (!empty($validated['rules_json'])) {
            $rules = json_decode($validated['rules_json'], true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                return back()->withErrors(['rules_json' => __('messages.modification_invalid_json', ['reason' => json_last_error_msg()])]);
            }
        }

        TenantModification::on('central')->create([
            'name' => $validated['name'],
            'code' => $validated['code'],
            'description' => $validated['description'],
            'version' => $validated['version'] ?? '1.0.0',
            'author' => $validated['author'],
            'status' => $validated['status'] ?? false,
            'tenant_ids' => $validated['tenant_ids'] ?? null,
            'rules' => $rules,
        ]);

        return redirect()->route('landlord.modifications.index')
            ->with('success', __('messages.modification_created', ['name' => $validated['name']]));
    }

    public function edit(TenantModification $modification)
    {
        $tenants = Tenant::on('central')
            ->select('id', 'name', 'status')
            ->orderBy('name')
            ->get();

        return Inertia::render('Landlord/Modifications/Form', [
            'modification' => array_merge($modification->toArray(), [
                'rules_json' => $modification->rules
                    ? json_encode($modification->rules, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
                    : '',
            ]),
            'tenants' => $tenants,
        ]);
    }

    public function update(Request $request, TenantModification $modification)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => "required|string|max:100|unique:central.tenant_modifications,code,{$modification->id}|alpha_dash",
            'description' => 'nullable|string|max:2000',
            'version' => 'nullable|string|max:20',
            'author' => 'nullable|string|max:100',
            'status' => 'boolean',
            'tenant_ids' => 'nullable|array',
            'tenant_ids.*' => 'string',
            'rules_json' => 'nullable|string',
        ]);

        $rules = null;
        if (!empty($validated['rules_json'])) {
            $rules = json_decode($validated['rules_json'], true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                return back()->withErrors(['rules_json' => __('messages.modification_invalid_json', ['reason' => json_last_error_msg()])]);
            }
        }

        $modification->update([
            'name' => $validated['name'],
            'code' => $validated['code'],
            'description' => $validated['description'],
            'version' => $validated['version'] ?? '1.0.0',
            'author' => $validated['author'],
            'status' => $validated['status'] ?? false,
            'tenant_ids' => $validated['tenant_ids'] ?? null,
            'rules' => $rules,
        ]);

        return redirect()->route('landlord.modifications.index')
            ->with('success', __('messages.modification_updated', ['name' => $modification->name]));
    }

    public function destroy(TenantModification $modification)
    {
        $name = $modification->name;
        $modification->delete();

        return back()->with('success', __('messages.modification_deleted', ['name' => $name]));
    }

    /**
     * "Apply" used to write patched files to storage/modifications/{tenant}/
     * and report success — but nothing in the request lifecycle ever loaded
     * or executed those files (resolveClass() has no callers anywhere), so
     * this silently did nothing while telling the admin it worked. It's also
     * a standing risk: the patch rules let a super-admin session write an
     * attacker-controlled preg_replace pattern/replacement into any file
     * under the app — safe only as long as nothing ever loads the result.
     * Disabled here (web-reachable) until the feature is actually finished
     * (autoloader hook + review/dry-run step). CLI-only application still
     * exists via `php artisan tenant-mod:apply` for anyone who already has
     * server access, which carries no additional risk beyond what they
     * already have.
     */
    public function apply(Request $request)
    {
        return back()->with('error', __('messages.modifications_not_applied'));
    }

    /**
     * Clear modifications cache for a specific tenant.
     */
    public function clear(Request $request, TenantModificationService $service)
    {
        $request->validate(['tenant_id' => 'required|string|exists:central.tenants,id']);

        $service->clearForTenant($request->tenant_id);

        return back()->with('success', __('messages.modification_cache_cleared'));
    }

    /**
     * Toggle a modification's status.
     */
    public function toggle(TenantModification $modification)
    {
        $modification->update(['status' => !$modification->status]);

        return back()->with('success', __(
            $modification->status ? 'messages.modification_enabled' : 'messages.modification_disabled',
            ['name' => $modification->name]
        ));
    }
}
