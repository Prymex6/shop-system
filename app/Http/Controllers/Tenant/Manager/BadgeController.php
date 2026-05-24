<?php

namespace App\Http\Controllers\Tenant\Manager;

use App\Http\Controllers\Controller;
use App\Models\Tenant\Badge;
use Illuminate\Http\Request;
use Inertia\Inertia;

class BadgeController extends Controller
{
    public function index()
    {
        $badges = Badge::orderBy('name')->get();

        return Inertia::render('Tenant/Manager/Badges/Index', [
            'badges' => $badges,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:100',
            'description' => 'nullable|string|max:255',
            'icon' => 'required|string|max:10',
            'condition_type' => 'required|in:orders_count,total_spent,review_count,referral_count',
            'condition_value' => 'required|integer|min:1',
        ]);

        Badge::create($data);

        return back()->with('success', __('messages.badge_added'));
    }

    public function update(Request $request, Badge $badge)
    {
        $data = $request->validate([
            'name' => 'required|string|max:100',
            'description' => 'nullable|string|max:255',
            'icon' => 'required|string|max:10',
            'condition_type' => 'required|in:orders_count,total_spent,review_count,referral_count',
            'condition_value' => 'required|integer|min:1',
        ]);

        $badge->update($data);

        return back()->with('success', __('messages.badge_updated'));
    }

    public function destroy(Badge $badge)
    {
        $badge->customers()->detach();
        $badge->delete();

        return back()->with('success', __('messages.badge_deleted'));
    }
}
