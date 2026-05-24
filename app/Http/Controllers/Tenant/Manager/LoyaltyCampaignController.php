<?php

namespace App\Http\Controllers\Tenant\Manager;

use App\Http\Controllers\Controller;
use App\Models\Tenant\Category;
use App\Models\Tenant\LoyaltyCampaign;
use App\Models\Tenant\Product;
use Illuminate\Http\Request;
use Inertia\Inertia;

class LoyaltyCampaignController extends Controller
{
    public function index()
    {
        $campaigns = LoyaltyCampaign::orderByDesc('created_at')->get();
        $products = Product::orderBy('name')->get(['id', 'name']);
        $categories = Category::orderBy('name')->get(['id', 'name']);

        return Inertia::render('Tenant/Manager/Loyalty/Campaigns', [
            'campaigns' => $campaigns,
            'products' => $products,
            'categories' => $categories,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'multiplier' => 'required|numeric|min:1.01|max:10',
            'applies_to' => 'required|in:all,category,product',
            'target_id' => 'nullable|integer',
            'day_of_week' => 'nullable|integer|between:0,6',
            'valid_from' => 'nullable|date',
            'valid_until' => 'nullable|date|after_or_equal:valid_from',
            'is_active' => 'boolean',
        ]);

        LoyaltyCampaign::create($validated);

        return redirect()->route('tenant.manager.loyalty.campaigns.index')
            ->with('success', __('messages.campaign_added'));
    }

    public function update(Request $request, LoyaltyCampaign $campaign)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'multiplier' => 'required|numeric|min:1.01|max:10',
            'applies_to' => 'required|in:all,category,product',
            'target_id' => 'nullable|integer',
            'day_of_week' => 'nullable|integer|between:0,6',
            'valid_from' => 'nullable|date',
            'valid_until' => 'nullable|date|after_or_equal:valid_from',
            'is_active' => 'boolean',
        ]);

        $campaign->update($validated);

        return redirect()->route('tenant.manager.loyalty.campaigns.index')
            ->with('success', __('messages.campaign_updated'));
    }

    public function destroy(LoyaltyCampaign $campaign)
    {
        $campaign->delete();

        return redirect()->route('tenant.manager.loyalty.campaigns.index')
            ->with('success', __('messages.campaign_removed'));
    }
}
