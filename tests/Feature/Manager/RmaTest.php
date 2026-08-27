<?php

namespace Tests\Feature\Manager;

use App\Models\Tenant\RmaRequest;
use App\Models\Tenant\User;
use Tests\TenantTestCase;

/**
 * Tests for RMA (Return Merchandise Authorization) management.
 *
 * Covers:
 *   – Manager can view the RMA request list
 *   – Manager can view a single RMA request detail
 *   – Manager can approve and reject RMA requests
 *   – Action is validated against the allowed values
 */
class RmaTest extends TenantTestCase
{
    private function manager(): User
    {
        return User::create([
            'name' => 'Manager', 'email' => 'mgr@test.com',
            'password' => bcrypt('s'), 'role' => 'manager', 'is_active' => true,
        ]);
    }

    private function makeRma(array $overrides = []): RmaRequest
    {
        $order = $this->createTestOrder();

        return RmaRequest::create(array_merge([
            'order_id' => $order->id,
            'rma_number' => 'RMA-' . uniqid(),
            'customer_email' => 'c@test.com',
            'customer_name' => 'Jan Klient',
            'reason' => 'Produkt uszkodzony',
            'status' => 'pending',
            'items' => [['name' => 'Produkt testowy', 'qty' => 1]],
        ], $overrides));
    }

    public function test_manager_can_view_rma_list(): void
    {
        $this->makeRma();

        $response = $this->actingAs($this->manager(), 'tenant')
            ->withoutTenantMiddleware()
            ->get(route('tenant.manager.rma.index'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('Tenant/Manager/Rma/Index')
            ->has('rmaList')
        );
    }

    public function test_manager_can_view_rma_detail(): void
    {
        $rma = $this->makeRma();

        $response = $this->actingAs($this->manager(), 'tenant')
            ->withoutTenantMiddleware()
            ->get(route('tenant.manager.rma.show', $rma));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('Tenant/Manager/Rma/Show')
            ->has('rma')
        );
    }

    public function test_manager_can_approve_rma(): void
    {
        $rma = $this->makeRma(['status' => 'pending']);

        $response = $this->actingAs($this->manager(), 'tenant')
            ->withoutTenantMiddleware()
            ->patchJson(route('tenant.manager.rma.update', $rma), [
                'action' => 'approve',
            ]);

        $response->assertRedirect();
        $rma->refresh();
        $this->assertEquals('approved', $rma->status);
    }

    public function test_manager_can_reject_rma(): void
    {
        $rma = $this->makeRma(['status' => 'pending']);

        $response = $this->actingAs($this->manager(), 'tenant')
            ->withoutTenantMiddleware()
            ->patchJson(route('tenant.manager.rma.update', $rma), [
                'action' => 'reject',
                'reason' => 'Brak podstaw do zwrotu.',
            ]);

        $response->assertRedirect();
        $rma->refresh();
        $this->assertEquals('rejected', $rma->status);
    }

    public function test_rma_action_must_be_valid(): void
    {
        $rma = $this->makeRma();

        $response = $this->actingAs($this->manager(), 'tenant')
            ->withoutTenantMiddleware()
            ->patchJson(route('tenant.manager.rma.update', $rma), [
                'action' => 'invalid',
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['action']);
    }
}
