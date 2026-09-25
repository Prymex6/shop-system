<?php

namespace App\Http\Controllers\Tenant\Manager;

use App\Http\Controllers\Controller;
use App\Models\Tenant\DiscountCode;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;

class DiscountCodeController extends Controller
{
    /**
     * Display a listing of discount codes
     */
    public function index(): Response
    {
        $codes = DiscountCode::query()
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($code) {
                return [
                    'id' => $code->id,
                    'code' => $code->code,
                    'type' => $code->type,
                    'value' => $code->value,
                    'formatted_value' => $code->formatted_value,
                    'min_order_value' => $code->min_order_value,
                    'max_uses' => $code->max_uses,
                    'used_count' => $code->used_count,
                    'usage_percentage' => $code->usage_percentage,
                    'valid_from' => $code->valid_from?->format('Y-m-d H:i'),
                    'valid_until' => $code->valid_until?->format('Y-m-d H:i'),
                    'is_active' => $code->is_active,
                    'created_at' => $code->created_at->format('Y-m-d H:i'),
                ];
            });

        return Inertia::render('Tenant/Manager/Discounts/Index', [
            'codes' => $codes,
        ]);
    }

    /**
     * Store a newly created discount code
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            // min:6 — a short code (e.g. "VIP", "10") is brute-forceable across
            // a rotated proxy pool despite the 15/60 per-IP throttle on
            // validate-discount; gift card codes get their entropy from
            // GiftCardService::generateUniqueCode() instead, this is the manager-typed
            // equivalent guard.
            'code' => 'required|string|min:6|max:50|unique:discount_codes,code',
            'type' => 'required|in:percentage,fixed',
            'value' => 'required|numeric|min:0',
            'min_order_value' => 'nullable|numeric|min:0',
            'max_uses' => 'nullable|integer|min:1',
            'valid_from' => 'nullable|date',
            'valid_until' => 'nullable|date|after:valid_from',
            'is_active' => 'boolean',
        ]);

        // Additional validation for percentage
        if ($validated['type'] === 'percentage' && $validated['value'] > 100) {
            return back()->withErrors([
                'value' => __('messages.discount_over_hundred'),
            ]);
        }

        $validated['code'] = strtoupper(trim($validated['code']));
        $validated['valid_from'] = $validated['valid_from'] ?? null;
        $validated['valid_until'] = $validated['valid_until'] ?? null;

        $code = DiscountCode::create($validated);
        Log::info('Discount: code created', ['code' => $code->code, 'type' => $code->type, 'value' => $code->value, 'manager_id' => auth('tenant')->id()]);

        return redirect()->route('tenant.manager.discounts.index')
            ->with('success', __('messages.discount_created'));
    }

    /**
     * Update the specified discount code
     */
    public function update(Request $request, DiscountCode $discountCode): RedirectResponse
    {
        $validated = $request->validate([
            'code' => 'required|string|min:6|max:50|unique:discount_codes,code,' . $discountCode->id,
            'type' => 'required|in:percentage,fixed',
            'value' => 'required|numeric|min:0',
            'min_order_value' => 'nullable|numeric|min:0',
            'max_uses' => 'nullable|integer|min:1',
            'valid_from' => 'nullable|date',
            'valid_until' => 'nullable|date|after:valid_from',
            'is_active' => 'boolean',
        ]);

        // Additional validation for percentage
        if ($validated['type'] === 'percentage' && $validated['value'] > 100) {
            return back()->withErrors([
                'value' => __('messages.discount_over_hundred'),
            ]);
        }

        $validated['code'] = strtoupper(trim($validated['code']));
        $validated['valid_from'] = $validated['valid_from'] ?? null;
        $validated['valid_until'] = $validated['valid_until'] ?? null;

        $discountCode->update($validated);
        Log::info('Discount: code updated', ['code' => $discountCode->code, 'manager_id' => auth('tenant')->id()]);

        return redirect()->route('tenant.manager.discounts.index')
            ->with('success', __('messages.discount_updated'));
    }

    /**
     * Remove the specified discount code
     */
    public function destroy(DiscountCode $discountCode): RedirectResponse
    {
        Log::info('Discount: code deleted', ['code' => $discountCode->code, 'manager_id' => auth('tenant')->id()]);
        $discountCode->delete();

        return redirect()->route('tenant.manager.discounts.index')
            ->with('success', __('messages.discount_deleted'));
    }

    /**
     * Toggle discount code active status
     */
    public function toggle(DiscountCode $discountCode): RedirectResponse
    {
        $newState = !$discountCode->is_active;
        $discountCode->update(['is_active' => $newState]);
        Log::info('Discount: zmiana statusu kodu', ['code' => $discountCode->code, 'active' => $newState, 'manager_id' => auth('tenant')->id()]);

        return redirect()->route('tenant.manager.discounts.index')
            ->with('success', __('messages.discount_status_changed'));
    }
}
