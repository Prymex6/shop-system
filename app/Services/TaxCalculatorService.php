<?php

namespace App\Services;

use App\Models\Tenant\Product;
use App\Models\Tenant\TaxRate;

class TaxCalculatorService
{
    /**
     * Calculate tax for a product at a given price.
     * Returns the tax amount (not the rate).
     *
     * If $countryCode is given and the shop has an active EU OSS rate
     * configured for that country (TaxController::storeOss()), it takes
     * priority over the product's own flat TaxRate — this is what actually
     * makes an OSS rate the manager configured in the panel apply to a real
     * order, instead of it sitting in the database unused while every
     * customer paid the product's plain default rate regardless of country.
     * Falls back to the existing flat-rate behavior whenever no matching
     * OSS rate is configured, so a shop that hasn't set one up sees no
     * change at all.
     */
    public function calculateForProduct(Product $product, float $price, int $quantity = 1, ?string $countryCode = null): array
    {
        $taxRate = $this->resolveOssRate($countryCode) ?? $product->taxRate ?? TaxRate::getDefault();
        $rate = $taxRate ? (float) $taxRate->rate : 0.0;

        $gross = $price * $quantity;
        $net = $rate > 0 ? round($gross / (1 + $rate / 100), 2) : $gross;
        $taxAmount = round($gross - $net, 2);

        return [
            'rate' => $rate,
            'net' => $net,
            'tax_amount' => $taxAmount,
            'gross' => $gross,
        ];
    }

    private function resolveOssRate(?string $countryCode): ?TaxRate
    {
        if (!$countryCode) {
            return null;
        }

        return TaxRate::where('is_eu_oss', true)
            ->where('is_active', true)
            ->where('country_code', strtoupper($countryCode))
            ->first();
    }
}
