<?php

namespace Tests\Feature\Manager;

use App\Http\Middleware\CheckPermission;
use App\Http\Middleware\CheckRole;
use App\Http\Middleware\Tenant\CheckSetupComplete;
use App\Http\Middleware\Tenant\CheckTenantLicense;
use App\Models\Tenant\Page;
use App\Models\Tenant\User;
use Stancl\Tenancy\Middleware\InitializeTenancyByDomain;
use Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains;
use Tests\TenantTestCase;

/**
 * Tests for the manager Page Builder (PageBuilderController).
 *
 * Covers:
 *   PB01 – Manager can view pages list
 *   PB02 – Manager can open create form
 *   PB03 – Manager can create a page (auto-generated slug)
 *   PB04 – Manager can create a page with explicit slug (slugified)
 *   PB05 – store() requires title
 *   PB06 – store() requires valid status (draft|published)
 *   PB07 – store() rejects duplicate slug
 *   PB08 – Manager can open edit form
 *   PB09 – Manager can update a page
 *   PB10 – Manager can publish a draft page
 *   PB11 – Manager can save blocks (array)
 *   PB12 – Manager can delete a page
 *   PB13 – update() requires title
 *   PB14 – Unauthenticated cannot access page builder
 */
class PageBuilderTest extends TenantTestCase
{
    private function manager(): User
    {
        return User::create([
            'name' => 'Manager',
            'email' => 'mgr@test.com',
            'password' => bcrypt('s'),
            'role' => 'manager',
            'is_active' => true,
        ]);
    }

    private function makePage(array $overrides = []): Page
    {
        return Page::create(array_merge([
            'title' => 'O nas',
            'slug' => 'o-nas',
            'status' => 'draft',
            'blocks' => [],
        ], $overrides));
    }

    // PB01 – Manager sees pages list
    public function test_manager_can_view_pages_list(): void
    {
        $this->makePage();
        $this->makePage(['title' => 'Kontakt', 'slug' => 'kontakt']);

        $response = $this->actingAs($this->manager(), 'tenant')
            ->withoutTenantMiddleware()
            ->get(route('tenant.manager.page-builder.index'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->has('pages', 2)
        );
    }

    // PB02 – Manager opens create form
    public function test_manager_can_open_create_form(): void
    {
        $response = $this->actingAs($this->manager(), 'tenant')
            ->withoutTenantMiddleware()
            ->get(route('tenant.manager.page-builder.create'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->where('page', null)
        );
    }

    // PB03 – Auto-generated slug from title
    public function test_manager_can_create_page_with_auto_slug(): void
    {
        $response = $this->actingAs($this->manager(), 'tenant')
            ->withoutTenantMiddleware()
            ->post(route('tenant.manager.page-builder.store'), [
                'title' => 'O nas i kontakt',
                'status' => 'draft',
                'blocks' => [],
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('pages', [
            'title' => 'O nas i kontakt',
            'slug' => 'o-nas-i-kontakt',
        ]);
    }

    // PB04 – Explicit slug is slugified
    public function test_manager_can_create_page_with_explicit_slug(): void
    {
        $response = $this->actingAs($this->manager(), 'tenant')
            ->withoutTenantMiddleware()
            ->post(route('tenant.manager.page-builder.store'), [
                'title' => 'Regulamin',
                'slug' => 'Regulamin Sklepu',
                'status' => 'draft',
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('pages', [
            'slug' => 'regulamin-sklepu',
        ]);
    }

    // PB05 – store() requires title
    public function test_store_requires_title(): void
    {
        $response = $this->actingAs($this->manager(), 'tenant')
            ->withoutTenantMiddleware()
            ->postJson(route('tenant.manager.page-builder.store'), [
                'status' => 'draft',
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['title']);
    }

    // PB06 – store() requires valid status
    public function test_store_requires_valid_status(): void
    {
        $response = $this->actingAs($this->manager(), 'tenant')
            ->withoutTenantMiddleware()
            ->postJson(route('tenant.manager.page-builder.store'), [
                'title' => 'Test',
                'status' => 'invalid_status',
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['status']);
    }

    // PB07 – Duplicate slug validation
    public function test_store_rejects_duplicate_slug(): void
    {
        $this->makePage(['title' => 'O nas', 'slug' => 'o-nas']);

        $response = $this->actingAs($this->manager(), 'tenant')
            ->withoutTenantMiddleware()
            ->postJson(route('tenant.manager.page-builder.store'), [
                'title' => 'O nas v2',
                'slug' => 'o-nas',
                'status' => 'draft',
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['slug']);
    }

    // PB08 – Manager opens edit form
    public function test_manager_can_open_edit_form(): void
    {
        $page = $this->makePage();

        $response = $this->actingAs($this->manager(), 'tenant')
            ->withoutTenantMiddleware()
            ->get(route('tenant.manager.page-builder.edit', $page));

        $response->assertStatus(200);
        $response->assertInertia(fn ($p) => $p->where('page.id', $page->id)
        );
    }

    // PB09 – Manager updates a page
    public function test_manager_can_update_page(): void
    {
        $page = $this->makePage(['title' => 'Stary tytuł', 'slug' => 'stary-tytul']);

        $response = $this->actingAs($this->manager(), 'tenant')
            ->withoutTenantMiddleware()
            ->put(route('tenant.manager.page-builder.update', $page), [
                'title' => 'Nowy tytuł',
                'slug' => 'nowy-tytul',
                'status' => 'draft',
                'blocks' => [],
            ]);

        $response->assertRedirect();
        $this->assertEquals('Nowy tytuł', $page->fresh()->title);
        $this->assertEquals('nowy-tytul', $page->fresh()->slug);
    }

    // PB10 – Manager publishes a draft page
    public function test_manager_can_publish_draft_page(): void
    {
        $page = $this->makePage(['status' => 'draft']);

        $response = $this->actingAs($this->manager(), 'tenant')
            ->withoutTenantMiddleware()
            ->put(route('tenant.manager.page-builder.update', $page), [
                'title' => $page->title,
                'slug' => $page->slug,
                'status' => 'published',
                'blocks' => [],
            ]);

        $response->assertRedirect();
        $this->assertEquals('published', $page->fresh()->status);
    }

    // PB11 – Manager saves blocks array
    public function test_manager_can_save_blocks(): void
    {
        $page = $this->makePage();
        $blocks = [
            ['type' => 'hero', 'title' => 'Witaj w sklepie', 'subtitle' => 'Najlepsze produkty'],
            ['type' => 'text', 'content' => 'Opis sklepu...'],
        ];

        $this->actingAs($this->manager(), 'tenant')
            ->withoutTenantMiddleware()
            ->put(route('tenant.manager.page-builder.update', $page), [
                'title' => $page->title,
                'slug' => $page->slug,
                'status' => 'draft',
                'blocks' => $blocks,
            ]);

        $saved = $page->fresh()->blocks;
        $this->assertCount(2, $saved);
        $this->assertEquals('hero', $saved[0]['type']);
        $this->assertEquals('text', $saved[1]['type']);
    }

    // PB12 – Manager deletes a page
    public function test_manager_can_delete_page(): void
    {
        $page = $this->makePage();

        $response = $this->actingAs($this->manager(), 'tenant')
            ->withoutTenantMiddleware()
            ->delete(route('tenant.manager.page-builder.destroy', $page));

        $response->assertRedirect();
        $this->assertDatabaseMissing('pages', ['id' => $page->id]);
    }

    // PB13 – update() requires title
    public function test_update_requires_title(): void
    {
        $page = $this->makePage();

        $response = $this->actingAs($this->manager(), 'tenant')
            ->withoutTenantMiddleware()
            ->putJson(route('tenant.manager.page-builder.update', $page), [
                'status' => 'draft',
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['title']);
    }

    // PB14 – Unauthenticated cannot access page builder (bypass tenancy init but keep auth guard)
    public function test_unauthenticated_cannot_access_page_builder(): void
    {
        $page = $this->makePage();

        $bypassTenancy = [
            InitializeTenancyByDomain::class,
            PreventAccessFromCentralDomains::class,
            CheckSetupComplete::class,
            CheckTenantLicense::class,
            CheckRole::class,
            CheckPermission::class,
        ];

        $this->withoutMiddleware($bypassTenancy)
            ->get(route('tenant.manager.page-builder.index'))
            ->assertRedirect();

        $this->withoutMiddleware($bypassTenancy)
            ->get(route('tenant.manager.page-builder.edit', $page))
            ->assertRedirect();
    }
}
