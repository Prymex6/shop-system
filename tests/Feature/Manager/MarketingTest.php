<?php

namespace Tests\Feature\Manager;

use App\Models\Tenant\EmailCampaign;
use App\Models\Tenant\User;
use Illuminate\Support\Facades\Queue;
use Tests\TenantTestCase;

/**
 * Tests for email marketing campaigns.
 */
class MarketingTest extends TenantTestCase
{
    private function manager(): User
    {
        return User::create([
            'name' => 'Manager', 'email' => 'mgr@test.com',
            'password' => bcrypt('s'), 'role' => 'manager', 'is_active' => true,
        ]);
    }

    public function test_marketing_page_loads(): void
    {
        $response = $this->actingAs($this->manager(), 'tenant')
            ->withoutTenantMiddleware()
            ->get(route('tenant.manager.marketing.index'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('Tenant/Manager/Marketing/Index')
            ->has('campaigns')
        );
    }

    public function test_manager_can_create_campaign_draft(): void
    {
        $response = $this->actingAs($this->manager(), 'tenant')
            ->withoutTenantMiddleware()
            ->postJson(route('tenant.manager.marketing.store'), [
                'name' => 'Letnia promocja',
                'subject' => 'Nowe menu na lato!',
                'content' => 'Sprawdź nasze nowe dania...',
                'target' => 'all',
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('email_campaigns', [
            'name' => 'Letnia promocja',
            'status' => 'draft',
        ]);
    }

    public function test_manager_can_update_campaign_draft(): void
    {
        $campaign = EmailCampaign::create([
            'name' => 'Stara Kampania',
            'subject' => 'Stary temat',
            'content' => 'Stara treść',
            'target' => 'all',
            'status' => 'draft',
        ]);
        $manager = $this->manager();

        $response = $this->actingAs($manager, 'tenant')
            ->withoutTenantMiddleware()
            ->putJson(route('tenant.manager.marketing.update', $campaign), [
                'name' => 'Nowa Kampania',
                'subject' => 'Nowy temat',
                'content' => 'Nowa treść',
                'target' => 'active',
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('email_campaigns', ['id' => $campaign->id, 'name' => 'Nowa Kampania']);
    }

    public function test_manager_can_delete_draft_campaign(): void
    {
        $campaign = EmailCampaign::create([
            'name' => 'Do Usunięcia', 'subject' => 'S', 'content' => 'C', 'target' => 'all', 'status' => 'draft',
        ]);
        $manager = $this->manager();

        $response = $this->actingAs($manager, 'tenant')
            ->withoutTenantMiddleware()
            ->deleteJson(route('tenant.manager.marketing.destroy', $campaign));

        $response->assertRedirect();
        $this->assertDatabaseMissing('email_campaigns', ['id' => $campaign->id]);
    }

    public function test_campaign_requires_name_and_subject(): void
    {
        $response = $this->actingAs($this->manager(), 'tenant')
            ->withoutTenantMiddleware()
            ->postJson(route('tenant.manager.marketing.store'), []);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['name', 'subject', 'content']);
    }

    public function test_campaign_target_must_be_valid(): void
    {
        $response = $this->actingAs($this->manager(), 'tenant')
            ->withoutTenantMiddleware()
            ->postJson(route('tenant.manager.marketing.store'), [
                'name' => 'Test',
                'subject' => 'S',
                'content' => 'C',
                'target' => 'invalid_target',
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['target']);
    }

    public function test_manager_can_send_campaign(): void
    {
        Queue::fake();

        $campaign = EmailCampaign::create([
            'name' => 'Do Wysłania', 'subject' => 'S', 'content' => 'C', 'target' => 'all', 'status' => 'draft',
        ]);
        $manager = $this->manager();

        $response = $this->actingAs($manager, 'tenant')
            ->withoutTenantMiddleware()
            ->postJson(route('tenant.manager.marketing.send', $campaign));

        $response->assertRedirect();
        $campaign->refresh();
        $this->assertEquals('sent', $campaign->status);
    }
}
