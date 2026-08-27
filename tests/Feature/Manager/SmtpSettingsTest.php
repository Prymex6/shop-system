<?php

namespace Tests\Feature\Manager;

use App\Models\Tenant\User;
use Tests\TenantTestCase;

/**
 * Tests for per-tenant SMTP configuration and the "test connection" endpoint.
 */
class SmtpSettingsTest extends TenantTestCase
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

    // ─── Settings page ─────────────────────────────────────────────────────

    public function test_settings_page_loads(): void
    {
        $response = $this->actingAs($this->manager(), 'tenant')
            ->withoutTenantMiddleware()
            ->get(route('tenant.manager.settings.index'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('Tenant/Manager/Settings/Index')
            ->has('settings')
        );
    }

    public function test_settings_can_be_updated(): void
    {
        $response = $this->actingAs($this->manager(), 'tenant')
            ->withoutTenantMiddleware()
            ->put(route('tenant.manager.settings.update'), [
                'shop_name' => 'Pizza Test',
                'shop_phone' => '123456789',
                'shop_email' => 'pizza@test.com',
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('tenant_settings', [
            'key' => 'shop_name',
            'value' => 'Pizza Test',
        ]);
    }

    // ─── testSmtp() validation ─────────────────────────────────────────────

    public function test_smtp_test_requires_email(): void
    {
        $response = $this->actingAs($this->manager(), 'tenant')
            ->withoutTenantMiddleware()
            ->postJson(route('tenant.manager.settings.test-smtp'), []);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['email']);
    }

    public function test_smtp_test_requires_valid_email_format(): void
    {
        $response = $this->actingAs($this->manager(), 'tenant')
            ->withoutTenantMiddleware()
            ->postJson(route('tenant.manager.settings.test-smtp'), [
                'email' => 'not-an-email',
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['email']);
    }

    public function test_smtp_test_with_valid_email_but_no_smtp_config_returns_failure(): void
    {
        // No SMTP settings configured — should attempt connection and fail gracefully
        $response = $this->actingAs($this->manager(), 'tenant')
            ->withoutTenantMiddleware()
            ->postJson(route('tenant.manager.settings.test-smtp'), [
                'email' => 'test@test.com',
            ]);

        // Either connection failure (422 + success=false) or misconfigured (any 4xx)
        $data = $response->json();
        $this->assertArrayHasKey('success', $data);
        $this->assertFalse($data['success']);
    }

    public function test_smtp_test_with_configured_but_unreachable_server_returns_failure(): void
    {
        $this->setSettings([
            'smtp_host' => '127.0.0.1',
            'smtp_port' => '9999',
            'smtp_username' => 'user',
            'smtp_password' => 'pass',
            'smtp_from_address' => 'from@test.com',
            'smtp_from_name' => 'Test Sender',
            'smtp_encryption' => 'tls',
        ]);

        $response = $this->actingAs($this->manager(), 'tenant')
            ->withoutTenantMiddleware()
            ->postJson(route('tenant.manager.settings.test-smtp'), [
                'email' => 'recipient@test.com',
            ]);

        $data = $response->json();
        $this->assertArrayHasKey('success', $data);
        // Connection to 127.0.0.1:9999 should fail
        $this->assertFalse($data['success']);
    }

    // ─── SMTP settings stored correctly ────────────────────────────────────

    public function test_smtp_settings_are_saved_via_settings_update(): void
    {
        $response = $this->actingAs($this->manager(), 'tenant')
            ->withoutTenantMiddleware()
            ->put(route('tenant.manager.settings.update'), [
                'smtp_host' => 'mail.example.com',
                'smtp_port' => '587',
                'smtp_username' => 'mailer@example.com',
                'smtp_from_address' => 'noreply@example.com',
                'smtp_from_name' => 'Pizza Restauracja',
                'smtp_encryption' => 'tls',
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('tenant_settings', [
            'key' => 'smtp_host',
            'value' => 'mail.example.com',
        ]);
        $this->assertDatabaseHas('tenant_settings', [
            'key' => 'smtp_port',
            'value' => '587',
        ]);
    }
}
