<?php

namespace Tests\Feature\Manager;

use App\Models\Tenant\Badge;
use App\Models\Tenant\User;
use Tests\TenantTestCase;

/**
 * Tests for manager badge CRUD management.
 */
class BadgeTest extends TenantTestCase
{
    private function manager(): User
    {
        return User::create([
            'name' => 'Manager', 'email' => 'mgr@test.com',
            'password' => bcrypt('s'), 'role' => 'manager', 'is_active' => true,
        ]);
    }

    public function test_manager_can_view_badges(): void
    {
        $response = $this->actingAs($this->manager(), 'tenant')
            ->withoutTenantMiddleware()
            ->get(route('tenant.manager.badges.index'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('Tenant/Manager/Badges/Index')
            ->has('badges')
        );
    }

    public function test_manager_can_create_badge(): void
    {
        $response = $this->actingAs($this->manager(), 'tenant')
            ->withoutTenantMiddleware()
            ->postJson(route('tenant.manager.badges.store'), [
                'name' => 'Złoty Klient',
                'description' => 'Za 10 zamówień',
                'icon' => 'star',
                'condition_type' => 'orders_count',
                'condition_value' => 10,
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('badges', [
            'name' => 'Złoty Klient',
            'icon' => 'star',
            'condition_type' => 'orders_count',
        ]);
    }

    public function test_manager_can_update_badge(): void
    {
        $badge = Badge::create([
            'name' => 'Stara Odznaka',
            'description' => 'Opis',
            'icon' => 'medal',
            'condition_type' => 'orders_count',
            'condition_value' => 5,
        ]);

        $response = $this->actingAs($this->manager(), 'tenant')
            ->withoutTenantMiddleware()
            ->patchJson(route('tenant.manager.badges.update', $badge), [
                'name' => 'Nowa Odznaka',
                'description' => 'Nowy opis',
                'icon' => 'trophy',
                'condition_type' => 'total_spent',
                'condition_value' => 1000,
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('badges', [
            'id' => $badge->id,
            'name' => 'Nowa Odznaka',
            'condition_type' => 'total_spent',
        ]);
    }

    public function test_manager_can_delete_badge(): void
    {
        $badge = Badge::create([
            'name' => 'Do Usunięcia',
            'icon' => 'trash',
            'condition_type' => 'review_count',
            'condition_value' => 3,
        ]);

        $response = $this->actingAs($this->manager(), 'tenant')
            ->withoutTenantMiddleware()
            ->deleteJson(route('tenant.manager.badges.destroy', $badge));

        $response->assertRedirect();
        $this->assertDatabaseMissing('badges', ['id' => $badge->id]);
    }

    public function test_badge_requires_name(): void
    {
        $response = $this->actingAs($this->manager(), 'tenant')
            ->withoutTenantMiddleware()
            ->postJson(route('tenant.manager.badges.store'), [
                'description' => 'Brak nazwy',
                'icon' => 'star',
                'condition_type' => 'orders_count',
                'condition_value' => 5,
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['name']);
    }
}
