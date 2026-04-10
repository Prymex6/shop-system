<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AddressAutocompleteService
{
    public function suggest(string $query): array
    {
        if (strlen($query) < 3) {
            return [];
        }

        try {
            $response = Http::timeout(3)
                ->get('https://mapa.poczta-polska.pl/api/autocomplete', [
                    'q' => $query,
                ]);

            if ($response->successful()) {
                $data = $response->json();

                return collect($data)->map(fn ($item) => [
                    'postal_code' => $item['postal_code'] ?? $item['postCode'] ?? '',
                    'city' => $item['city'] ?? $item['cityName'] ?? '',
                    'street' => $item['street'] ?? $item['streetName'] ?? '',
                ])->filter(fn ($item) => !empty($item['city']))->values()->toArray();
            }
        } catch (\Throwable $e) {
            // Graceful fallback — log and return empty
            Log::debug('AddressAutocomplete API failed: ' . $e->getMessage());
        }

        return [];
    }
}
