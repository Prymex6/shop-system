<?php

namespace App\Services;

class EuVatService
{
    public const EU_COUNTRIES = [
        'AT', 'BE', 'BG', 'CY', 'CZ', 'DE', 'DK', 'EE', 'ES', 'FI',
        'FR', 'GR', 'HR', 'HU', 'IE', 'IT', 'LT', 'LU', 'LV', 'MT',
        'NL', 'PL', 'PT', 'RO', 'SE', 'SI', 'SK',
    ];

    // Standard VAT rates for EU countries (%)
    public const EU_VAT_RATES = [
        'AT' => 20.0, 'BE' => 21.0, 'BG' => 20.0, 'CY' => 19.0,
        'CZ' => 21.0, 'DE' => 19.0, 'DK' => 25.0, 'EE' => 22.0,
        'ES' => 21.0, 'FI' => 25.5, 'FR' => 20.0, 'GR' => 24.0,
        'HR' => 25.0, 'HU' => 27.0, 'IE' => 23.0, 'IT' => 22.0,
        'LT' => 21.0, 'LU' => 17.0, 'LV' => 21.0, 'MT' => 18.0,
        'NL' => 21.0, 'PL' => 23.0, 'PT' => 23.0, 'RO' => 19.0,
        'SE' => 25.0, 'SI' => 22.0, 'SK' => 20.0,
    ];

    public function isEuCountry(string $countryCode): bool
    {
        return in_array(strtoupper($countryCode), self::EU_COUNTRIES);
    }

    public function getVatRate(string $countryCode, string $productType = 'standard'): float
    {
        $code = strtoupper($countryCode);

        if (!$this->isEuCountry($code)) {
            return 0.0;
        }

        $standardRate = self::EU_VAT_RATES[$code] ?? 23.0;

        // Reduced rates for some product types (simplified)
        if ($productType === 'food' || $productType === 'books') {
            return match ($code) {
                'DE' => 7.0,
                'FR' => 5.5,
                'PL' => 5.0,
                'AT' => 10.0,
                'IT' => 4.0,
                default => round($standardRate * 0.25, 1),
            };
        }

        return $standardRate;
    }
}
