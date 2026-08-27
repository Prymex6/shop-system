<?php

namespace Tests\Feature;

use App\Models\Tenant\Customer;
use App\Services\LoyaltyService;
use Tests\TenantTestCase;

/**
 * Tests for loyalty points accumulation, tiers, and redemption.
 */
class LoyaltyTest extends TenantTestCase
{
    private function customer(int $points = 0): Customer
    {
        return Customer::create([
            'name' => 'Lojalny Klient',
            'email' => 'loyal@test.com',
            'phone' => '123456789',
            'loyalty_points' => $points,
            'loyalty_tier' => 'bronze',
        ]);
    }

    public function test_loyalty_service_awards_points_for_order(): void
    {
        $this->setSettings([
            'loyalty_enabled' => '1',
            'loyalty_points_per_pln' => '1',
        ]);

        $customer = $this->customer(0);
        $order = $this->createTestOrder([
            'customer_id' => $customer->id,
            'customer_email' => 'loyal@test.com',
            'total' => 50.00,
            'payment_status' => 'paid',
        ]);

        $service = app(LoyaltyService::class);
        $service->awardPointsForOrder($order);

        $customer->refresh();
        $this->assertGreaterThan(0, $customer->loyalty_points);
    }

    public function test_loyalty_points_scale_with_order_total(): void
    {
        $this->setSettings([
            'loyalty_enabled' => '1',
            'loyalty_earn_mode' => 'per_pln',
            'loyalty_points_per_pln' => '2',
        ]);

        $customer = $this->customer(0);
        $order = $this->createTestOrder([
            'customer_id' => $customer->id,
            'customer_email' => 'loyal@test.com',
            'total' => 40.00,
            'payment_status' => 'paid',
        ]);

        $service = app(LoyaltyService::class);
        $service->awardPointsForOrder($order);

        $customer->refresh();
        $this->assertEquals(80, $customer->loyalty_points);
    }

    public function test_loyalty_not_awarded_when_disabled(): void
    {
        $this->setSettings(['loyalty_enabled' => '0']);

        $customer = $this->customer(0);
        $order = $this->createTestOrder([
            'customer_id' => $customer->id,
            'customer_email' => 'loyal@test.com',
            'total' => 50.00,
            'payment_status' => 'paid',
        ]);

        $service = app(LoyaltyService::class);
        $service->awardPointsForOrder($order);

        $customer->refresh();
        $this->assertEquals(0, $customer->loyalty_points);
    }

    public function test_tier_upgrades_with_enough_points(): void
    {
        $this->setSettings([
            'loyalty_enabled' => '1',
            'loyalty_earn_mode' => 'per_pln',
            'loyalty_points_per_pln' => '1',
            'loyalty_tiers' => json_encode([
                'bronze' => ['min' => 0,    'name' => 'Brązowy', 'color' => '#cd7f32', 'multiplier' => 1.0],
                'silver' => ['min' => 200,  'name' => 'Srebrny',  'color' => '#9ca3af', 'multiplier' => 1.25],
                'gold' => ['min' => 500,  'name' => 'Złoty',    'color' => '#f59e0b', 'multiplier' => 1.5],
                'platinum' => ['min' => 1000, 'name' => 'Platynowy', 'color' => '#60a5fa', 'multiplier' => 2.0],
            ]),
        ]);

        $customer = $this->customer(0);
        $order = $this->createTestOrder([
            'customer_id' => $customer->id,
            'customer_email' => 'loyal@test.com',
            'total' => 250.00,
            'payment_status' => 'paid',
        ]);

        $service = app(LoyaltyService::class);
        $service->awardPointsForOrder($order);
        $service->updateTier($customer->fresh());

        $customer->refresh();
        $this->assertEquals('silver', $customer->loyalty_tier);
    }

    public function test_loyalty_points_history_is_recorded(): void
    {
        $this->setSettings(['loyalty_enabled' => '1', 'loyalty_points_per_pln' => '1']);

        $customer = $this->customer(0);
        $order = $this->createTestOrder([
            'customer_id' => $customer->id,
            'customer_email' => 'loyal@test.com',
            'total' => 30.00,
            'payment_status' => 'paid',
        ]);

        app(LoyaltyService::class)->awardPointsForOrder($order);

        $this->assertDatabaseHas('loyalty_points', [
            'customer_id' => $customer->id,
            'type' => 'earned',
        ]);
    }
}
