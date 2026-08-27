<?php

namespace Tests\Feature\Manager;

use App\Models\Tenant\User;
use Tests\TenantTestCase;

/**
 * Tests for the manager support-ticket interface.
 *
 * The SupportController uses cross-database queries (tenant DB + landlord DB).
 * Full page-load and creation tests require both databases to be set up with
 * a proper tenant context (tenancy()->initialize($tenant)).
 *
 * The tests here cover:
 *   - Validation failures that are returned BEFORE any cross-DB query fires.
 *   - If you need full integration tests, run with a real MySQL setup and a
 *     proper tenant context — the cross-DB pattern can't be tested with the
 *     SQLite :memory: TenantTestCase (no landlord tables / no active tenant).
 */
class SupportTest extends TenantTestCase
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

    // ─── store() validation (fires before cross-DB calls) ─────────────────

    public function test_create_ticket_requires_subject(): void
    {
        $response = $this->actingAs($this->manager(), 'tenant')
            ->withoutTenantMiddleware()
            ->postJson(route('tenant.manager.support.store'), [
                'message' => 'Treść zgłoszenia',
                'priority' => 'normal',
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['subject']);
    }

    public function test_create_ticket_requires_message(): void
    {
        $response = $this->actingAs($this->manager(), 'tenant')
            ->withoutTenantMiddleware()
            ->postJson(route('tenant.manager.support.store'), [
                'subject' => 'Problem z systemem',
                'priority' => 'normal',
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['message']);
    }

    public function test_create_ticket_requires_valid_priority(): void
    {
        $response = $this->actingAs($this->manager(), 'tenant')
            ->withoutTenantMiddleware()
            ->postJson(route('tenant.manager.support.store'), [
                'subject' => 'Problem',
                'message' => 'Treść',
                'priority' => 'super-urgent', // invalid
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['priority']);
    }

    public function test_create_ticket_accepts_valid_priorities(): void
    {
        $manager = $this->manager(); // create once; avoid duplicate email in loop

        foreach (['low', 'normal', 'high', 'urgent'] as $priority) {
            $response = $this->actingAs($manager, 'tenant')
                ->withoutTenantMiddleware()
                ->postJson(route('tenant.manager.support.store'), [
                    'subject' => 'Test',
                    'message' => 'Treść',
                    'priority' => $priority,
                ]);

            // Validation passes — request proceeds to DB (may 500 in this test env
            // without landlord DB, but must NOT be a 422 validation error).
            $this->assertNotEquals(422, $response->getStatusCode(),
                "Priority '$priority' should be valid but got 422"
            );
        }
    }

    public function test_subject_max_length_validated(): void
    {
        $response = $this->actingAs($this->manager(), 'tenant')
            ->withoutTenantMiddleware()
            ->postJson(route('tenant.manager.support.store'), [
                'subject' => str_repeat('a', 256),
                'message' => 'Treść',
                'priority' => 'normal',
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['subject']);
    }

    // ─── reply() validation (fires before cross-DB calls) ─────────────────

    public function test_reply_requires_message(): void
    {
        $response = $this->actingAs($this->manager(), 'tenant')
            ->withoutTenantMiddleware()
            ->postJson(route('tenant.manager.support.reply', 999), []);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['message']);
    }

    public function test_reply_message_max_length_validated(): void
    {
        $response = $this->actingAs($this->manager(), 'tenant')
            ->withoutTenantMiddleware()
            ->postJson(route('tenant.manager.support.reply', 999), [
                'message' => str_repeat('a', 5001),
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['message']);
    }
}
