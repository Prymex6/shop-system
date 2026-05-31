<?php

namespace App\Services;

use App\Models\Tenant\Setting;

class TrustBadgeService
{
    public function getTrustpilotUrl(): ?string
    {
        $id = Setting::get('trustpilot_business_id');
        if (!$id) {
            return null;
        }

        return "https://www.trustpilot.com/review/{$id}";
    }

    public function getGoogleReviewsUrl(): ?string
    {
        $placeId = Setting::get('google_place_id');
        if (!$placeId) {
            return null;
        }

        return "https://search.google.com/local/writereview?placeid={$placeId}";
    }

    public function getSchema(): array
    {
        $shopName = Setting::get('shop_name', config('app.name'));

        return [
            '@context' => 'https://schema.org',
            '@type' => 'Organization',
            'name' => $shopName,
            'aggregateRating' => [
                '@type' => 'AggregateRating',
                'ratingValue' => '4.8',
                'reviewCount' => '0',
                'bestRating' => '5',
                'worstRating' => '1',
            ],
        ];
    }
}
