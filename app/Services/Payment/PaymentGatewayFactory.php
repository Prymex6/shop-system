<?php

namespace App\Services\Payment;

use App\Contracts\PaymentGatewayInterface;
use InvalidArgumentException;

class PaymentGatewayFactory
{
    /**
     * Create gateway instance by payment method name
     */
    public static function make(string $method): PaymentGatewayInterface
    {
        return match ($method) {
            'przelewy24', 'online' => new Przelewy24Gateway,
            'payu' => new PayUGateway,
            'tpay' => new TpayGateway,
            default => throw new InvalidArgumentException("Unknown payment gateway: {$method}"),
        };
    }

    /**
     * Returns list of all online gateways with metadata
     */
    public static function allGateways(): array
    {
        return [
            'przelewy24' => [
                'label' => 'Przelewy24',
                'description' => 'Przelew, BLIK, karta – ponad 200 metod',
                'logo' => 'P24',
                'color' => '#cc1b2a',
                'setting_key' => 'payment_p24_enabled', // setting prefix differs from method name
            ],
            'payu' => [
                'label' => 'PayU',
                'description' => 'Szybki przelew, BLIK, karta',
                'logo' => 'PayU',
                'color' => '#00b3e3',
                'setting_key' => 'payment_payu_enabled',
            ],
            'tpay' => [
                'label' => 'Tpay',
                'description' => 'Szybkie przelewy i BLIK',
                'logo' => 'Tpay',
                'color' => '#3d9bff',
                'setting_key' => 'payment_tpay_enabled',
            ],
        ];
    }

    /**
     * Returns only gateways that are configured for current tenant
     */
    public static function configuredGateways(): array
    {
        $all = self::allGateways();
        $configured = [];

        foreach ($all as $method => $meta) {
            try {
                $gateway = self::make($method);
                if ($gateway->isConfigured()) {
                    $configured[$method] = $meta;
                }
            } catch (\Exception) {
                // skip
            }
        }

        return $configured;
    }
}
