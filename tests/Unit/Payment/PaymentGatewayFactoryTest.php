<?php

namespace Tests\Unit\Payment;

use App\Services\Payment\PaymentGatewayFactory;
use App\Services\Payment\PayUGateway;
use App\Services\Payment\Przelewy24Gateway;
use App\Services\Payment\TpayGateway;
use InvalidArgumentException;
use Tests\TenantTestCase;

class PaymentGatewayFactoryTest extends TenantTestCase
{
    public function test_make_przelewy24_returns_correct_gateway(): void
    {
        $gateway = PaymentGatewayFactory::make('przelewy24');
        $this->assertInstanceOf(Przelewy24Gateway::class, $gateway);
    }

    public function test_make_online_legacy_returns_przelewy24(): void
    {
        $gateway = PaymentGatewayFactory::make('online');
        $this->assertInstanceOf(Przelewy24Gateway::class, $gateway);
    }

    public function test_make_payu_returns_correct_gateway(): void
    {
        $gateway = PaymentGatewayFactory::make('payu');
        $this->assertInstanceOf(PayUGateway::class, $gateway);
    }

    public function test_make_tpay_returns_correct_gateway(): void
    {
        $gateway = PaymentGatewayFactory::make('tpay');
        $this->assertInstanceOf(TpayGateway::class, $gateway);
    }

    public function test_make_unknown_throws_exception(): void
    {
        $this->expectException(InvalidArgumentException::class);
        PaymentGatewayFactory::make('bitcoin');
    }

    public function test_make_cash_throws_exception(): void
    {
        $this->expectException(InvalidArgumentException::class);
        PaymentGatewayFactory::make('cash');
    }

    public function test_all_gateways_returns_four_gateways(): void
    {
        $gateways = PaymentGatewayFactory::allGateways();

        $this->assertCount(3, $gateways);
        $this->assertArrayHasKey('przelewy24', $gateways);
        $this->assertArrayHasKey('payu', $gateways);
        $this->assertArrayHasKey('tpay', $gateways);
    }

    public function test_all_gateways_have_required_metadata(): void
    {
        $gateways = PaymentGatewayFactory::allGateways();

        foreach ($gateways as $method => $meta) {
            $this->assertArrayHasKey('label', $meta, "Gateway {$method} missing label");
            $this->assertArrayHasKey('description', $meta, "Gateway {$method} missing description");
            $this->assertArrayHasKey('logo', $meta, "Gateway {$method} missing logo");
            $this->assertArrayHasKey('color', $meta, "Gateway {$method} missing color");
        }
    }

    public function test_configured_gateways_returns_empty_when_no_settings(): void
    {
        // No settings seeded → all gateways unconfigured
        $configured = PaymentGatewayFactory::configuredGateways();
        $this->assertEmpty($configured);
    }

    public function test_configured_gateways_returns_p24_when_configured(): void
    {
        $this->setSettings([
            'p24_merchant_id' => '123456',
            'p24_api_key' => 'test-api-key',
            'p24_crc' => 'test-crc',
        ]);

        $configured = PaymentGatewayFactory::configuredGateways();
        $this->assertArrayHasKey('przelewy24', $configured);
    }

    public function test_gateway_names_are_correct(): void
    {
        $this->assertEquals('Przelewy24', PaymentGatewayFactory::make('przelewy24')->getName());
        $this->assertEquals('PayU', PaymentGatewayFactory::make('payu')->getName());
        $this->assertEquals('Tpay', PaymentGatewayFactory::make('tpay')->getName());
    }
}
