<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CurrencyService
{
    public const SUPPORTED = ['PLN', 'EUR', 'USD', 'GBP', 'CZK'];

    public function getSupportedCurrencies(): array
    {
        return self::SUPPORTED;
    }

    /**
     * Get exchange rate via NBP API. PLN is the base currency.
     * NBP API: https://api.nbp.pl/api/exchangerates/rates/A/{currency}/?format=json
     * Returns how many PLN 1 unit of $currency costs.
     */
    public function getRate(string $from, string $to): float
    {
        if ($from === $to) {
            return 1.0;
        }

        // Convert both to PLN first
        $fromInPln = $this->toPln($from);
        $toInPln = $this->toPln($to);

        if ($toInPln == 0) {
            return 1.0;
        }

        return $fromInPln / $toInPln;
    }

    private function toPln(string $currency): float
    {
        if ($currency === 'PLN') {
            return 1.0;
        }

        return Cache::remember("currency_rate_{$currency}", 3600, function () use ($currency) {
            try {
                $response = Http::timeout(5)->get(
                    "https://api.nbp.pl/api/exchangerates/rates/A/{$currency}/?format=json"
                );

                if ($response->successful()) {
                    $data = $response->json();

                    return (float) ($data['rates'][0]['mid'] ?? 1.0);
                }
            } catch (\Exception $e) {
                Log::warning("NBP API error for {$currency}: " . $e->getMessage());
            }

            // Fallback rates
            return match ($currency) {
                'EUR' => 4.25,
                'USD' => 3.90,
                'GBP' => 4.95,
                'CZK' => 0.17,
                default => 1.0,
            };
        });
    }

    public function convert(float $amount, string $from, string $to): float
    {
        if ($from === $to) {
            return $amount;
        }

        $rate = $this->getRate($from, $to);

        return round($amount * $rate, 2);
    }

    public function formatAmount(float $amount, string $currency): string
    {
        $symbols = [
            'PLN' => 'zł',
            'EUR' => '€',
            'USD' => '$',
            'GBP' => '£',
            'CZK' => 'Kč',
        ];

        $symbol = $symbols[$currency] ?? $currency;
        $formatted = number_format($amount, 2, ',', ' ');

        // Symbol before amount for USD/GBP/EUR, after for PLN/CZK
        if (in_array($currency, ['USD', 'GBP'])) {
            return $symbol . $formatted;
        }

        return $formatted . ' ' . $symbol;
    }
}
