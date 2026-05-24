<?php

namespace App\Http\Controllers\Tenant\Manager;

use App\Http\Controllers\Controller;
use App\Models\Tenant\LoyaltyReward;
use App\Models\Tenant\Product;
use Illuminate\Http\Request;
use Inertia\Inertia;

class LoyaltyRewardController extends Controller
{
    public function index()
    {
        $rewards = LoyaltyReward::orderBy('sort_order')->orderBy('cost_points')->get();
        $products = Product::with(['variants' => fn ($q) => $q->orderBy('sort_order')])->orderBy('name')->get(['id', 'name']);

        return Inertia::render('Tenant/Manager/Loyalty/Rewards', [
            'rewards' => $rewards,
            'products' => $products,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:500',
            'cost_points' => 'required|integer|min:1',
            'type' => 'required|in:free_delivery,fixed_discount,percent_discount,free_product',
            'value' => 'required|numeric|min:0',
            'product_id' => 'nullable|integer|exists:products,id',
            'variant_id' => 'nullable|integer|exists:product_variants,id',
            'is_active' => 'boolean',
            'valid_from' => 'nullable|date',
            'valid_until' => 'nullable|date|after_or_equal:valid_from',
            'max_uses' => 'nullable|integer|min:1',
            'sort_order' => 'integer',
        ]);

        LoyaltyReward::create($validated);

        return redirect()->route('tenant.manager.loyalty.rewards.index')
            ->with('success', __('messages.reward_added'));
    }

    public function update(Request $request, LoyaltyReward $reward)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:500',
            'cost_points' => 'required|integer|min:1',
            'type' => 'required|in:free_delivery,fixed_discount,percent_discount,free_product',
            'value' => 'required|numeric|min:0',
            'product_id' => 'nullable|integer|exists:products,id',
            'variant_id' => 'nullable|integer|exists:product_variants,id',
            'is_active' => 'boolean',
            'valid_from' => 'nullable|date',
            'valid_until' => 'nullable|date|after_or_equal:valid_from',
            'max_uses' => 'nullable|integer|min:1',
            'sort_order' => 'integer',
        ]);

        $reward->update($validated);

        return redirect()->route('tenant.manager.loyalty.rewards.index')
            ->with('success', __('messages.reward_updated'));
    }

    public function destroy(LoyaltyReward $reward)
    {
        $reward->delete();

        return redirect()->route('tenant.manager.loyalty.rewards.index')
            ->with('success', __('messages.reward_deleted'));
    }
}
