<?php

namespace Tests\Feature\Manager;

use App\Models\Tenant\GiftCard;
use App\Models\Tenant\User;
use Tests\TenantTestCase;

/**
 * Tests for gift card management.
 */
class GiftCardTest extends TenantTestCase
{
    private function manager(): User
    {
        return User::create([
            'name' => 'Manager', 'email' => 'mgr@test.com',
            'password' => bcrypt('s'), 'role' => 'manager', 'is_active' => true,
        ]);
    }

    public function test_manager_can_view_gift_cards(): void
    {
        $response = $this->actingAs($this->manager(), 'tenant')
            ->withoutTenantMiddleware()
            ->get(route('tenant.manager.gift-cards.index'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('Tenant/Manager/GiftCards/Index')
            ->has('giftCards')
        );
    }

    public function test_manager_can_generate_gift_cards(): void
    {
        $response = $this->actingAs($this->manager(), 'tenant')
            ->withoutTenantMiddleware()
            ->postJson(route('tenant.manager.gift-cards.store'), [
                'count' => 3,
                'value' => 50.00,
                'expires_at' => now()->addYear()->format('Y-m-d'),
            ]);

        $response->assertRedirect();
        $this->assertDatabaseCount('gift_cards', 3);
    }

    public function test_gift_card_generation_requires_count_and_value(): void
    {
        $response = $this->actingAs($this->manager(), 'tenant')
            ->withoutTenantMiddleware()
            ->postJson(route('tenant.manager.gift-cards.store'), []);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['count', 'value']);
    }

    public function test_manager_can_toggle_gift_card(): void
    {
        $card = GiftCard::create([
            'code' => 'TESTCODE123',
            'current_value' => 100.00,
            'initial_value' => 100.00,
            'is_active' => true,
            'expires_at' => null,
        ]);

        $response = $this->actingAs($this->manager(), 'tenant')
            ->withoutTenantMiddleware()
            ->patchJson(route('tenant.manager.gift-cards.toggle', $card));

        $response->assertRedirect();
        $card->refresh();
        $this->assertFalse((bool) $card->is_active);
    }

    public function test_manager_can_delete_gift_card(): void
    {
        $card = GiftCard::create([
            'code' => 'DELCODE456',
            'current_value' => 25.00,
            'initial_value' => 25.00,
            'is_active' => true,
            'expires_at' => null,
        ]);

        $response = $this->actingAs($this->manager(), 'tenant')
            ->withoutTenantMiddleware()
            ->deleteJson(route('tenant.manager.gift-cards.destroy', $card));

        $response->assertRedirect();
        $this->assertDatabaseMissing('gift_cards', ['id' => $card->id]);
    }

    public function test_count_must_be_between_1_and_100(): void
    {
        $manager = $this->manager();

        $response = $this->actingAs($manager, 'tenant')
            ->withoutTenantMiddleware()
            ->postJson(route('tenant.manager.gift-cards.store'), [
                'count' => 200,
                'value' => 10.00,
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['count']);

        $response = $this->actingAs($manager, 'tenant')
            ->withoutTenantMiddleware()
            ->postJson(route('tenant.manager.gift-cards.store'), [
                'count' => 0,
                'value' => 10.00,
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['count']);
    }
}
