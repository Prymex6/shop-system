<?php

namespace App\Services;

use App\Models\Tenant\Setting;
use App\Models\Tenant\ShippingMethod;
use App\Models\Tenant\ShippingZone;

class ShippingCalculatorService
{
    /**
     * Global "free shipping from X" — Settings/Index.vue has had this field
     * since before this class existed (labelled "Darmowa dostawa od (PLN)"),
     * but SettingsController::update() never whitelisted it for saving and
     * nothing here ever read it, so it was a fully dead, unsaveable setting.
     * Applied on top of any per-method/per-zone free_from — whichever grants
     * free shipping first wins.
     */
    private function globalFreeShippingThreshold(): ?float
    {
        $threshold = (float) Setting::get('free_shipping_threshold', 0);

        return $threshold > 0 ? $threshold : null;
    }

    /**
     * Get available shipping methods for a given country and cart subtotal.
     */
    public function getAvailableMethods(string $country, float $subtotal, float $totalWeight = 0): array
    {
        $zone = $this->resolveZone($country);

        if ($zone) {
            $methods = $zone->methods()->where('is_active', true)->where('type', '!=', 'digital')->orderBy('sort_order')->get();
            $methods = $methods->map(fn ($m) => $this->applyZoneOverrides($m, $zone, $subtotal));
        } else {
            $methods = ShippingMethod::active()->where('type', '!=', 'digital')->get();
            $methods = $methods->map(fn ($m) => $this->applyDefaultPricing($m, $subtotal));
        }

        // Filter by weight
        if ($totalWeight > 0) {
            $methods = $methods->filter(function ($m) use ($totalWeight) {
                $tooLight = $m->weight_min && $totalWeight < $m->weight_min;
                $tooHeavy = $m->weight_max && $totalWeight > $m->weight_max;

                return !$tooLight && !$tooHeavy;
            });
        }

        return $methods->values()->toArray();
    }

    /**
     * Calculate shipping cost for a specific method + order, or null if the
     * method isn't actually available for this country/zone — a method that
     * was never attached to the resolved zone used to silently fall back to
     * its own base price instead of being rejected, letting a customer pick
     * a cheap domestic-only method's ID while shipping to an expensive zone.
     */
    public function calculateCost(ShippingMethod $method, string $country, float $subtotal): ?float
    {
        if (!$method->is_active || $method->type === 'digital') {
            return null;
        }

        $zone = $this->resolveZone($country);

        if (!$zone) {
            return null;
        }

        $pivot = $zone->methods()->where('method_id', $method->id)->first()?->pivot;
        if (!$pivot) {
            return null;
        }

        $globalThreshold = $this->globalFreeShippingThreshold();
        if ($globalThreshold && $subtotal >= $globalThreshold) {
            return 0.0;
        }

        $freeFrom = $pivot->free_from_override ?? $method->free_from;
        if ($freeFrom && $subtotal >= $freeFrom) {
            return 0.0;
        }

        return (float) ($pivot->price_override ?? $method->price);
    }

    // ─── Private ─────────────────────────────────────────────────────

    private function resolveZone(string $country): ?ShippingZone
    {
        return ShippingZone::whereJsonContains('countries', $country)->first()
            ?? ShippingZone::where('is_default', true)->first();
    }

    private function applyZoneOverrides(ShippingMethod $method, ShippingZone $zone, float $subtotal): ShippingMethod
    {
        $clone = clone $method;
        $pivot = $zone->methods()->where('method_id', $method->id)->first()?->pivot;
        $freeFrom = ($pivot?->free_from_override) ?? $method->free_from;
        $price = ($pivot?->price_override) ?? $method->price;

        $globalThreshold = $this->globalFreeShippingThreshold();
        $isFree = ($globalThreshold && $subtotal >= $globalThreshold) || ($freeFrom && $subtotal >= $freeFrom);

        $clone->effective_price = $isFree ? 0.0 : (float) $price;
        $clone->effective_free_from = $freeFrom;

        return $clone;
    }

    private function applyDefaultPricing(ShippingMethod $method, float $subtotal): ShippingMethod
    {
        $clone = clone $method;

        $globalThreshold = $this->globalFreeShippingThreshold();
        $clone->effective_price = ($globalThreshold && $subtotal >= $globalThreshold)
            ? 0.0
            : $method->priceForOrder($subtotal);
        $clone->effective_free_from = $method->free_from;

        return $clone;
    }
}
