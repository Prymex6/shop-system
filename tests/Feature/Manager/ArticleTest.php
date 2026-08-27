<?php

namespace Tests\Feature\Manager;

use App\Models\Tenant\Article;
use App\Models\Tenant\User;
use Tests\TenantTestCase;

/**
 * Tests for blog article management (CRUD + publish/unpublish).
 */
class ArticleTest extends TenantTestCase
{
    private function manager(): User
    {
        return User::create([
            'name' => 'Manager', 'email' => 'mgr@test.com',
            'password' => bcrypt('s'), 'role' => 'manager', 'is_active' => true,
        ]);
    }

    public function test_manager_can_view_articles(): void
    {
        $response = $this->actingAs($this->manager(), 'tenant')
            ->withoutTenantMiddleware()
            ->get(route('tenant.manager.articles.index'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('Tenant/Manager/Articles/Index')
            ->has('articles')
        );
    }

    public function test_manager_can_view_article_create_form(): void
    {
        $response = $this->actingAs($this->manager(), 'tenant')
            ->withoutTenantMiddleware()
            ->get(route('tenant.manager.articles.create'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('Tenant/Manager/Articles/Form', false)
        );
    }

    public function test_manager_can_create_article(): void
    {
        $response = $this->actingAs($this->manager(), 'tenant')
            ->withoutTenantMiddleware()
            ->postJson(route('tenant.manager.articles.store'), [
                'title' => 'Nowy artykuł testowy',
                'slug' => 'nowy-artykul-testowy',
                'content' => 'Treść artykułu testowego.',
                'status' => 'draft',
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('articles', [
            'title' => 'Nowy artykuł testowy',
            'slug' => 'nowy-artykul-testowy',
            'status' => 'draft',
        ]);
    }

    public function test_article_requires_title_content_and_status(): void
    {
        $response = $this->actingAs($this->manager(), 'tenant')
            ->withoutTenantMiddleware()
            ->postJson(route('tenant.manager.articles.store'), []);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['title', 'content', 'status']);
    }

    public function test_manager_can_edit_article(): void
    {
        $manager = $this->manager();
        $article = Article::create([
            'title' => 'Stary tytuł',
            'slug' => 'stary-tytul',
            'content' => 'Stara treść.',
            'status' => 'draft',
            'author_id' => $manager->id,
        ]);

        $response = $this->actingAs($manager, 'tenant')
            ->withoutTenantMiddleware()
            ->get(route('tenant.manager.articles.edit', $article));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('Tenant/Manager/Articles/Form', false)
            ->has('article')
        );
    }

    public function test_manager_can_update_article(): void
    {
        $manager = $this->manager();
        $article = Article::create([
            'title' => 'Stary tytuł',
            'slug' => 'stary-tytul',
            'content' => 'Stara treść.',
            'status' => 'draft',
            'author_id' => $manager->id,
        ]);

        $response = $this->actingAs($manager, 'tenant')
            ->withoutTenantMiddleware()
            ->putJson(route('tenant.manager.articles.update', $article), [
                'title' => 'Nowy tytuł',
                'slug' => 'nowy-tytul',
                'content' => 'Nowa treść.',
                'status' => 'published',
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('articles', ['id' => $article->id, 'title' => 'Nowy tytuł', 'status' => 'published']);
    }

    public function test_manager_can_toggle_article_status(): void
    {
        $manager = $this->manager();
        $article = Article::create([
            'title' => 'Artykuł do toggle',
            'slug' => 'artykul-toggle',
            'content' => 'Treść.',
            'status' => 'published',
            'author_id' => $manager->id,
        ]);

        $response = $this->actingAs($manager, 'tenant')
            ->withoutTenantMiddleware()
            ->patchJson(route('tenant.manager.articles.toggle-status', $article));

        $response->assertRedirect();
        $article->refresh();
        $this->assertEquals('draft', $article->status);
    }

    public function test_manager_can_delete_article(): void
    {
        $manager = $this->manager();
        $article = Article::create([
            'title' => 'Do usunięcia',
            'slug' => 'do-usuniecia',
            'content' => 'Treść.',
            'status' => 'draft',
            'author_id' => $manager->id,
        ]);

        $response = $this->actingAs($manager, 'tenant')
            ->withoutTenantMiddleware()
            ->deleteJson(route('tenant.manager.articles.destroy', $article));

        $response->assertRedirect();
        $this->assertDatabaseMissing('articles', ['id' => $article->id]);
    }
}
