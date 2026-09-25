<?php

namespace App\Http\Controllers\Tenant\Manager;

use App\Http\Controllers\Controller;
use App\Models\Tenant\Promotion;
use App\Models\Tenant\Setting;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PromotionController extends Controller
{
    public function index()
    {
        $promotions = Promotion::latest()->get();

        return Inertia::render('Tenant/Manager/Promotions/Index', [
            'promotions' => $promotions,
            'settings' => Setting::getAllAsArray(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'banner_text' => ['required', 'string', 'max:1000'],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['required', 'date', 'after:starts_at'],
            'discount_code' => ['nullable', 'string', 'max:50'],
            'is_active' => ['boolean'],
        ]);

        Promotion::create($data);

        return back()->with('success', __('messages.promotion_created'));
    }

    public function update(Request $request, Promotion $promotion)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'banner_text' => ['required', 'string', 'max:1000'],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['required', 'date', 'after:starts_at'],
            'discount_code' => ['nullable', 'string', 'max:50'],
            'is_active' => ['boolean'],
        ]);

        $promotion->update($data);

        return back()->with('success', __('messages.promotion_updated'));
    }

    public function saveBanner(Request $request)
    {
        $data = $request->validate([
            'announcement_enabled' => ['nullable', 'boolean'],
            'announcement_text' => ['nullable', 'string', 'max:500'],
            'announcement_color' => ['nullable', 'string', 'regex:/^#[0-9a-fA-F]{6}$/'],
        ]);

        Setting::set('announcement_enabled', (bool) ($data['announcement_enabled'] ?? false), 'boolean');
        Setting::set('announcement_text', $data['announcement_text'] ?? '', 'string');
        Setting::set('announcement_color', $data['announcement_color'] ?? '#4F46E5', 'string');

        return back()->with('success', __('messages.banner_updated'));
    }

    public function destroy(Promotion $promotion)
    {
        $promotion->delete();

        return back()->with('success', __('messages.promotion_deleted'));
    }
}
