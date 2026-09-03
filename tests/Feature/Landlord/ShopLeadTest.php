<?php

namespace Tests\Feature\Landlord;

use App\Models\Landlord\ShopLead;
use Tests\LandlordTestCase;

/**
 * Tests for ShopSearchController::toggleContacted()
 * and access-control on the shop-search routes.
 */
class ShopLeadTest extends LandlordTestCase
{
    // ─── toggleContacted ────────────────────────────────────────────────────

    public function test_toggle_contacted_creates_lead_and_marks_contacted(): void
    {
        $response = $this->actingAs($this->superAdmin(), 'super_admin')
            ->postJson(route('landlord.shop-search.toggle-contacted'), [
                'osm_id' => 123456789,
                'name' => 'Sklep Testowy',
                'type' => 'Odzież',
                'address' => 'ul. Testowa 1, Warszawa',
                'city' => 'Warszawa',
            ]);

        $response->assertOk();
        $data = $response->json();
        $this->assertNotNull($data['contacted_at'], 'contacted_at should be set after first toggle');

        $this->assertDatabaseHas('shop_leads', [
            'osm_id' => 123456789,
            'name' => 'Sklep Testowy',
        ], 'central');
    }

    public function test_toggle_contacted_unmarks_if_already_contacted(): void
    {
        // Create a lead that is already contacted
        ShopLead::create([
            'osm_id' => 987654321,
            'name' => 'Sklep Już Skontaktowany',
            'contacted_at' => now(),
        ]);

        $response = $this->actingAs($this->superAdmin(), 'super_admin')
            ->postJson(route('landlord.shop-search.toggle-contacted'), [
                'osm_id' => 987654321,
                'name' => 'Sklep Już Skontaktowany',
            ]);

        $response->assertOk();
        $this->assertNull($response->json('contacted_at'), 'contacted_at should be cleared on second toggle');
    }

    public function test_toggle_contacted_requires_osm_id(): void
    {
        $response = $this->actingAs($this->superAdmin(), 'super_admin')
            ->postJson(route('landlord.shop-search.toggle-contacted'), [
                'name' => 'Sklep Bez ID',
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['osm_id']);
    }

    public function test_toggle_contacted_requires_name(): void
    {
        $response = $this->actingAs($this->superAdmin(), 'super_admin')
            ->postJson(route('landlord.shop-search.toggle-contacted'), [
                'osm_id' => 111,
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['name']);
    }

    public function test_toggle_contacted_requires_integer_osm_id(): void
    {
        $response = $this->actingAs($this->superAdmin(), 'super_admin')
            ->postJson(route('landlord.shop-search.toggle-contacted'), [
                'osm_id' => 'nie-liczba',
                'name' => 'Sklep',
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['osm_id']);
    }

    // ─── Access control ─────────────────────────────────────────────────────

    public function test_unauthenticated_cannot_access_shop_search(): void
    {
        $response = $this->get(route('landlord.shop-search.index'));

        $response->assertRedirect();
        // Should redirect to admin login, not show the page
        $this->assertStringContainsString('login', $response->headers->get('Location') ?? '');
    }

    public function test_unauthenticated_cannot_toggle_contacted(): void
    {
        $response = $this->postJson(route('landlord.shop-search.toggle-contacted'), [
            'osm_id' => 1,
            'name' => 'Test',
        ]);

        // Unauthenticated API request → 401 or redirect (302)
        $this->assertContains($response->getStatusCode(), [302, 401, 403]);
    }

    // ─── Idempotency ────────────────────────────────────────────────────────

    public function test_toggle_contacted_does_not_duplicate_lead(): void
    {
        $admin = $this->superAdmin();
        $payload = ['osm_id' => 555, 'name' => 'Sklep Duplikat'];

        $this->actingAs($admin, 'super_admin')
            ->postJson(route('landlord.shop-search.toggle-contacted'), $payload);

        $this->actingAs($admin, 'super_admin')
            ->postJson(route('landlord.shop-search.toggle-contacted'), $payload);

        $count = ShopLead::on('central')->where('osm_id', 555)->count();
        $this->assertEquals(1, $count, 'Only one ShopLead row should exist regardless of toggle count');
    }
}
