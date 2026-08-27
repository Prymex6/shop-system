<?php

namespace Tests\Feature\Manager;

use App\Models\Tenant\FraudBlocklist;
use App\Models\Tenant\User;
use Tests\TenantTestCase;

/**
 * Tests for fraud detection management.
 *
 * Covers:
 *   – Manager can view the fraud-flagged orders list (Inertia page)
 *   – Manager can release or block a flagged order
 *   – Manager can view, add to and remove from the blocklist (JSON API)
 *   – Blocklist type is validated against email/ip/card_bin
 */
class FraudTest extends TenantTestCase
{
    private function manager(): User
    {
        return User::create([
            'name' => 'Manager', 'email' => 'mgr@test.com',
            'password' => bcrypt('s'), 'role' => 'manager', 'is_active' => true,
        ]);
    }

    public function test_manager_can_view_fraud_list(): void
    {
        $response = $this->actingAs($this->manager(), 'tenant')
            ->withoutTenantMiddleware()
            ->get(route('tenant.manager.fraud.index'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('Tenant/Manager/Fraud/Index')
        );
    }

    public function test_manager_can_release_flagged_order(): void
    {
        $order = $this->createTestOrder(['status' => 'pending']);

        $response = $this->actingAs($this->manager(), 'tenant')
            ->withoutTenantMiddleware()
            ->postJson(route('tenant.manager.fraud.release', $order));

        $response->assertRedirect();
    }

    public function test_manager_can_block_order(): void
    {
        $order = $this->createTestOrder(['status' => 'pending']);

        $response = $this->actingAs($this->manager(), 'tenant')
            ->withoutTenantMiddleware()
            ->postJson(route('tenant.manager.fraud.block', $order));

        $response->assertRedirect();
    }

    public function test_block_respects_status_transition_guard(): void
    {
        // Already refunded orders have no further allowed transition —
        // block() must not blindly overwrite status/payment_status.
        $order = $this->createTestOrder(['status' => 'refunded', 'payment_status' => 'refunded']);

        $this->actingAs($this->manager(), 'tenant')
            ->withoutTenantMiddleware()
            ->postJson(route('tenant.manager.fraud.block', $order));

        $order->refresh();
        $this->assertEquals('refunded', $order->status);
    }

    public function test_manager_can_view_fraud_blocklist(): void
    {
        $response = $this->actingAs($this->manager(), 'tenant')
            ->withoutTenantMiddleware()
            ->get(route('tenant.manager.fraud.blocklist.index'));

        $response->assertOk();
        $response->assertJsonStructure([]);
    }

    public function test_manager_can_add_ip_to_blocklist(): void
    {
        $response = $this->actingAs($this->manager(), 'tenant')
            ->withoutTenantMiddleware()
            ->postJson(route('tenant.manager.fraud.blocklist.store'), [
                'type' => 'ip',
                'value' => '192.168.1.100',
            ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('fraud_blocklist', [
            'type' => 'ip',
            'value' => '192.168.1.100',
        ]);
    }

    public function test_manager_can_add_email_to_blocklist(): void
    {
        $response = $this->actingAs($this->manager(), 'tenant')
            ->withoutTenantMiddleware()
            ->postJson(route('tenant.manager.fraud.blocklist.store'), [
                'type' => 'email',
                'value' => 'spammer@example.com',
            ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('fraud_blocklist', [
            'type' => 'email',
            'value' => 'spammer@example.com',
        ]);
    }

    public function test_manager_can_remove_from_blocklist(): void
    {
        $entry = FraudBlocklist::create([
            'type' => 'ip',
            'value' => '10.0.0.1',
        ]);

        $response = $this->actingAs($this->manager(), 'tenant')
            ->withoutTenantMiddleware()
            ->deleteJson(route('tenant.manager.fraud.blocklist.destroy', $entry));

        $response->assertOk();
        $this->assertDatabaseMissing('fraud_blocklist', ['id' => $entry->id]);
    }

    public function test_blocklist_type_must_be_valid(): void
    {
        $response = $this->actingAs($this->manager(), 'tenant')
            ->withoutTenantMiddleware()
            ->postJson(route('tenant.manager.fraud.blocklist.store'), [
                'type' => 'phone',
                'value' => '+48123456789',
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['type']);
    }
}
