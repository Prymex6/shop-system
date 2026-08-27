<?php

namespace Tests\Feature\Manager;

use App\Models\Tenant\Customer;
use App\Models\Tenant\User;
use Illuminate\Support\Facades\Event;
use Tests\TenantTestCase;

/**
 * Referral bonus is awarded to the referrer once the referred customer's
 * first order is marked delivered (OrderManagementController::updateStatus).
 * A same-registration-IP guard blocks the trivial self-referral farming case
 * (one person registering two accounts to pay themselves the bonus).
 */
class ReferralBonusTest extends TenantTestCase
{
    private function manager(): User
    {
        return User::create([
            'name' => 'Manager', 'email' => 'manager@test.com',
            'password' => bcrypt('secret'), 'role' => 'manager', 'is_active' => true,
        ]);
    }

    private function referrerAndReferred(string $referrerIp, string $referredIp): array
    {
        $referrer = Customer::create([
            'name' => 'Poleceniodawca', 'email' => 'referrer@test.com',
            'password' => bcrypt('s'), 'registration_ip' => $referrerIp,
        ]);

        $referred = Customer::create([
            'name' => 'Polecony', 'email' => 'referred@test.com',
            'password' => bcrypt('s'), 'registration_ip' => $referredIp,
            'loyalty_referred_by' => $referrer->id,
            'referral_bonus_awarded' => false,
        ]);

        return [$referrer, $referred];
    }

    public function test_referral_bonus_awarded_when_ips_differ(): void
    {
        Event::fake();
        $this->setSetting('loyalty_enabled', '1', 'boolean');
        $this->setSetting('loyalty_bonus_referral', '200', 'integer');

        [$referrer, $referred] = $this->referrerAndReferred('1.1.1.1', '2.2.2.2');
        $order = $this->createTestOrder([
            'customer_id' => $referred->id,
            'status' => 'processing',
        ]);

        $this->actingAs($this->manager(), 'tenant')
            ->withoutTenantMiddleware()
            ->patchJson(route('tenant.manager.orders.update-status', $order), ['status' => 'delivered'])
            ->assertRedirect();

        $this->assertEquals(200, $referrer->fresh()->loyalty_points);
        $this->assertTrue((bool) $referred->fresh()->referral_bonus_awarded);
    }

    public function test_referral_bonus_skipped_when_same_registration_ip(): void
    {
        Event::fake();
        $this->setSetting('loyalty_enabled', '1', 'boolean');
        $this->setSetting('loyalty_bonus_referral', '200', 'integer');

        [$referrer, $referred] = $this->referrerAndReferred('9.9.9.9', '9.9.9.9');
        $order = $this->createTestOrder([
            'customer_id' => $referred->id,
            'status' => 'processing',
        ]);

        $this->actingAs($this->manager(), 'tenant')
            ->withoutTenantMiddleware()
            ->patchJson(route('tenant.manager.orders.update-status', $order), ['status' => 'delivered'])
            ->assertRedirect();

        $this->assertEquals(0, $referrer->fresh()->loyalty_points);
    }
}
