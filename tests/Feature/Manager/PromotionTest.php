<?php

namespace Tests\Feature\Manager;

use App\Models\Tenant\Promotion;
use App\Models\Tenant\User;
use Tests\TenantTestCase;

/**
 * Tests for promotion banner management.
 *
 * Covers:
 *   – Manager can view promotions list
 *   – Manager can create, update and delete a promotion
 *   – Required fields are validated (name, banner_text, starts_at, ends_at)
 */
class PromotionTest extends TenantTestCase
{
    private function manager(): User
    {
        return User::create([
            'name' => 'Manager', 'email' => 'mgr@test.com',
            'password' => bcrypt('s'), 'role' => 'manager', 'is_active' => true,
        ]);
    }

    private function promotionData(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Wiosenna Wyprzedaż',
            'banner_text' => 'Do -30% na wszystkie produkty!',
            'starts_at' => now()->format('Y-m-d H:i:s'),
            'ends_at' => now()->addDays(7)->format('Y-m-d H:i:s'),
            'discount_code' => null,
            'is_active' => true,
        ], $overrides);
    }

    public function test_manager_can_view_promotions(): void
    {
        $response = $this->actingAs($this->manager(), 'tenant')
            ->withoutTenantMiddleware()
            ->get(route('tenant.manager.promotions.index'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('Tenant/Manager/Promotions/Index')
            ->has('promotions')
        );
    }

    public function test_manager_can_create_promotion(): void
    {
        $response = $this->actingAs($this->manager(), 'tenant')
            ->withoutTenantMiddleware()
            ->postJson(route('tenant.manager.promotions.store'), $this->promotionData());

        $response->assertRedirect();
        $this->assertDatabaseHas('promotions', [
            'name' => 'Wiosenna Wyprzedaż',
            'banner_text' => 'Do -30% na wszystkie produkty!',
        ]);
    }

    public function test_manager_can_create_promotion_with_discount_code(): void
    {
        $response = $this->actingAs($this->manager(), 'tenant')
            ->withoutTenantMiddleware()
            ->postJson(route('tenant.manager.promotions.store'), $this->promotionData([
                'name' => 'Black Friday',
                'banner_text' => 'Użyj kodu BF50 i oszczędź 50%!',
                'discount_code' => 'BF50',
            ]));

        $response->assertRedirect();
        $this->assertDatabaseHas('promotions', [
            'name' => 'Black Friday',
            'discount_code' => 'BF50',
        ]);
    }

    public function test_manager_can_update_promotion(): void
    {
        $promotion = Promotion::create($this->promotionData(['name' => 'Old Promo']));

        $response = $this->actingAs($this->manager(), 'tenant')
            ->withoutTenantMiddleware()
            ->putJson(route('tenant.manager.promotions.update', $promotion), $this->promotionData([
                'name' => 'Updated Promo',
                'banner_text' => 'Nowy tekst bannera',
            ]));

        $response->assertRedirect();
        $this->assertDatabaseHas('promotions', [
            'id' => $promotion->id,
            'name' => 'Updated Promo',
            'banner_text' => 'Nowy tekst bannera',
        ]);
    }

    public function test_manager_can_delete_promotion(): void
    {
        $promotion = Promotion::create($this->promotionData(['name' => 'To Delete']));

        $response = $this->actingAs($this->manager(), 'tenant')
            ->withoutTenantMiddleware()
            ->deleteJson(route('tenant.manager.promotions.destroy', $promotion));

        $response->assertRedirect();
        $this->assertDatabaseMissing('promotions', ['id' => $promotion->id]);
    }

    public function test_promotion_requires_banner_text(): void
    {
        $response = $this->actingAs($this->manager(), 'tenant')
            ->withoutTenantMiddleware()
            ->postJson(route('tenant.manager.promotions.store'), [
                'name' => 'Promo bez bannera',
                'starts_at' => now()->format('Y-m-d H:i:s'),
                'ends_at' => now()->addDay()->format('Y-m-d H:i:s'),
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['banner_text']);
    }

    public function test_promotion_requires_dates(): void
    {
        $response = $this->actingAs($this->manager(), 'tenant')
            ->withoutTenantMiddleware()
            ->postJson(route('tenant.manager.promotions.store'), [
                'name' => 'Promo bez dat',
                'banner_text' => 'Tekst bannera',
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['starts_at', 'ends_at']);
    }
}
