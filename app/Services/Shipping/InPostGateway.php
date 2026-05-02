<?php

namespace App\Services\Shipping;

use App\Helpers\PhoneHelper;
use App\Models\Tenant\Order;
use App\Models\Tenant\Setting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * InPost ShipX: parcel lockers, labels and delivery status.
 *
 * Credentials belong to the shop, not to the platform, so they are read from
 * that tenant's settings on every call rather than held on the instance —
 * a manager who pastes a new token does not have to wait for anything to be
 * rebuilt.
 *
 * Every method here fails soft except {@see createShipment()}. Looking up
 * lockers or a delivery status is something a page does while rendering, and
 * a carrier having a bad afternoon should not take the shop down with it.
 * Buying a label is a deliberate action with money attached, so that one
 * throws and says why.
 */
class InPostGateway
{
    private const BASE_URL = 'https://api.inpost.pl/v1';

    /** Lockers move rarely; asking for the same city on every checkout does not. */
    private const POINTS_CACHE_TTL = 86400;

    public function isConfigured(): bool
    {
        return filled($this->token()) && filled($this->organisationId());
    }

    private function token(): ?string
    {
        return Setting::get('inpost_api_token');
    }

    private function organisationId(): ?string
    {
        return Setting::get('inpost_organization_id');
    }

    /**
     * Operating parcel lockers, narrowed by city.
     *
     * @return list<array{code:string,name:string,street:string,city:string,postCode:string,description:string,latitude:?float,longitude:?float}>
     */
    public function points(string $city): array
    {
        $city = trim($city);

        if ($city === '' || !$this->isConfigured()) {
            return [];
        }

        return Cache::remember(
            'inpost.points.' . mb_strtolower($city),
            self::POINTS_CACHE_TTL,
            fn () => $this->fetchPoints($city),
        );
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function fetchPoints(string $city): array
    {
        try {
            $response = Http::timeout(15)
                ->withToken($this->token())
                ->get(self::BASE_URL . '/points', [
                    'type' => 'parcel_locker',
                    'status' => 'Operating',
                    'city' => $city,
                    'per_page' => 100,
                ]);

            if (!$response->successful()) {
                Log::warning('InPost: could not list parcel lockers', [
                    'city' => $city,
                    'status' => $response->status(),
                ]);

                return [];
            }

            return array_values(array_map(
                fn (array $point) => $this->normalisePoint($point),
                $response->json('items', []),
            ));
        } catch (\Throwable $error) {
            Log::error('InPost: parcel locker lookup failed', [
                'city' => $city,
                'error' => $error->getMessage(),
            ]);

            return [];
        }
    }

    /**
     * The handful of fields the checkout shows, out of the sixty ShipX sends.
     *
     * @param array<string, mixed> $point
     * @return array<string, mixed>
     */
    private function normalisePoint(array $point): array
    {
        $address = $point['address_details'] ?? [];
        $location = $point['location'] ?? [];

        $street = trim(($address['street'] ?? '') . ' ' . ($address['building_number'] ?? ''));

        return [
            'code' => (string) ($point['name'] ?? ''),
            'name' => (string) ($point['location_description'] ?? $point['name'] ?? ''),
            'street' => $street,
            'city' => (string) ($address['city'] ?? ''),
            'postCode' => (string) ($address['post_code'] ?? ''),
            'description' => (string) ($point['location_description'] ?? ''),
            'latitude' => isset($location['latitude']) ? (float) $location['latitude'] : null,
            'longitude' => isset($location['longitude']) ? (float) $location['longitude'] : null,
        ];
    }

    /**
     * Buy a label for an order the customer chose a locker for.
     *
     * A locker delivery carries no street address: the parcel goes to the
     * locker, and the customer is found by phone and email. That is why
     * nothing here reads the order's shipping address.
     *
     * @return array{id:string,trackingNumber:?string,status:?string}
     */
    public function createShipment(Order $order): array
    {
        if (!$this->isConfigured()) {
            throw new RuntimeException('InPost is not configured for this shop.');
        }

        if (blank($order->pickup_point_code)) {
            throw new RuntimeException('This order has no parcel locker on it.');
        }

        $phone = PhoneHelper::normalize($order->customer_phone);

        if ($phone === null) {
            throw new RuntimeException('InPost needs a phone number to notify the customer with.');
        }

        $response = Http::timeout(30)
            ->withToken($this->token())
            ->post(self::BASE_URL . '/organizations/' . $this->organisationId() . '/shipments', [
                'receiver' => [
                    'name' => $order->customer_name,
                    'email' => $order->customer_email,
                    'phone' => $phone,
                ],
                'parcels' => [
                    ['template' => 'small'],
                ],
                'custom_attributes' => [
                    'target_point' => $order->pickup_point_code,
                    'sending_method' => 'parcel_locker',
                ],
                'service' => 'inpost_locker_standard',
                'reference' => $order->order_number,
            ]);

        if (!$response->successful()) {
            Log::error('InPost: label purchase rejected', [
                'order' => $order->order_number,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            throw new RuntimeException(
                'InPost rejected the shipment (HTTP ' . $response->status() . ').'
            );
        }

        return [
            'id' => (string) $response->json('id'),
            'trackingNumber' => $response->json('tracking_number'),
            'status' => $response->json('status'),
        ];
    }

    /**
     * Where a parcel is now, by tracking number.
     *
     * Returns null when the carrier cannot be reached, which is different
     * from a parcel whose status is genuinely unknown — the caller leaves
     * the order alone rather than recording a status that never came.
     */
    public function trackingStatus(string $trackingNumber): ?string
    {
        if (!$this->isConfigured()) {
            return null;
        }

        try {
            $response = Http::timeout(10)
                ->withToken($this->token())
                ->get(self::BASE_URL . '/tracking/' . $trackingNumber);

            return $response->successful() ? $response->json('status') : null;
        } catch (\Throwable $error) {
            Log::error('InPost: tracking lookup failed', [
                'tracking' => $trackingNumber,
                'error' => $error->getMessage(),
            ]);

            return null;
        }
    }
}
