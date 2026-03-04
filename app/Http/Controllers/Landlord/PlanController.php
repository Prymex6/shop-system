<?php

namespace App\Http\Controllers\Landlord;

use App\Http\Controllers\Controller;
use App\Models\Landlord\Plan;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;

class PlanController extends Controller
{
    public function index()
    {
        return Inertia::render('Landlord/Plans/Index', [
            'plans' => Plan::withCount('tenants')->get(),
        ]);
    }

    public function create()
    {
        return Inertia::render('Landlord/Plans/Form');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'price' => ['nullable', 'numeric', 'min:0'],
            'max_orders_per_month' => ['nullable', 'integer', 'min:0'],
            'max_products' => ['nullable', 'integer', 'min:0'],
            'max_staff' => ['nullable', 'integer', 'min:0'],
            'max_storage_mb' => ['nullable', 'integer', 'min:0'],
            'features' => ['nullable', 'array'],
            'features.*' => ['string', 'max:255'],
            'is_active' => ['boolean'],
        ]);

        $validated['slug'] = Str::slug($validated['name']);
        $validated['is_active'] = $validated['is_active'] ?? true;

        Plan::create($validated);

        return redirect()->route('landlord.plans.index')
            ->with('success', "Plan '{$validated['name']}' został utworzony!");
    }

    public function edit(Plan $plan)
    {
        return Inertia::render('Landlord/Plans/Form', [
            'plan' => $plan,
        ]);
    }

    public function update(Request $request, Plan $plan)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'price' => ['nullable', 'numeric', 'min:0'],
            'max_orders_per_month' => ['nullable', 'integer', 'min:0'],
            'max_products' => ['nullable', 'integer', 'min:0'],
            'max_staff' => ['nullable', 'integer', 'min:0'],
            'max_storage_mb' => ['nullable', 'integer', 'min:0'],
            'features' => ['nullable', 'array'],
            'features.*' => ['string', 'max:255'],
            'is_active' => ['boolean'],
        ]);

        $validated['slug'] = Str::slug($validated['name']);
        $plan->update($validated);

        return redirect()->route('landlord.plans.index')
            ->with('success', "Plan '{$plan->name}' został zaktualizowany!");
    }

    public function destroy(Plan $plan)
    {
        if ($plan->tenants()->exists()) {
            return back()->with('error', __('messages.plan_in_use'));
        }

        $name = $plan->name;
        $plan->delete();

        return back()->with('success', "Plan '{$name}' został usunięty.");
    }
}
