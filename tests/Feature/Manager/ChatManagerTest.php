<?php

namespace Tests\Feature\Manager;

use App\Http\Middleware\CheckPermission;
use App\Http\Middleware\CheckRole;
use App\Http\Middleware\Tenant\CheckSetupComplete;
use App\Http\Middleware\Tenant\CheckTenantLicense;
use App\Models\Tenant\ChatConversation;
use App\Models\Tenant\ChatMessage;
use App\Models\Tenant\User;
use Stancl\Tenancy\Middleware\InitializeTenancyByDomain;
use Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains;
use Tests\TenantTestCase;

/**
 * Tests for the manager Live Chat panel (ChatManagerController).
 *
 * Covers:
 *   CM01 – Manager sees list of conversations
 *   CM02 – Manager sees single conversation + messages marked as read
 *   CM03 – Manager can reply to a conversation
 *   CM04 – Reply requires non-empty body
 *   CM05 – Reply body max 2000 characters
 *   CM06 – Manager can close a conversation
 *   CM07 – Manager can poll messages
 *   CM08 – Manager cannot reply to a closed conversation
 *   CM09 – Unauthenticated user cannot access chat manager
 */
class ChatManagerTest extends TenantTestCase
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

    private function makeConversation(array $overrides = []): ChatConversation
    {
        return ChatConversation::create(array_merge([
            'guest_name' => 'Jan Gość',
            'guest_email' => 'jan@test.com',
            'customer_id' => null,
            'status' => 'open',
            'last_message_at' => now(),
        ], $overrides));
    }

    private function addMessage(ChatConversation $conv, string $sender, string $body = 'Treść'): ChatMessage
    {
        return $conv->messages()->create([
            'sender' => $sender,
            'body' => $body,
        ]);
    }

    // CM01 – Manager sees list of conversations
    public function test_manager_can_view_conversations_list(): void
    {
        $this->makeConversation();
        $this->makeConversation(['guest_name' => 'Anna Klient', 'guest_email' => 'anna@test.com']);

        $response = $this->actingAs($this->manager(), 'tenant')
            ->withoutTenantMiddleware()
            ->get(route('tenant.manager.chat.index'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->has('conversations')
        );
    }

    // CM02 – Manager opens conversation — customer messages marked as read
    public function test_manager_can_view_conversation_and_marks_messages_read(): void
    {
        $conv = $this->makeConversation();
        $msg = $this->addMessage($conv, 'customer', 'Hej, potrzebuję pomocy');
        $this->assertNull($msg->read_at);

        $response = $this->actingAs($this->manager(), 'tenant')
            ->withoutTenantMiddleware()
            ->get(route('tenant.manager.chat.show', $conv));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->has('messages')
            ->has('conversation')
        );

        $this->assertNotNull($msg->fresh()->read_at);
    }

    // CM03 – Manager replies successfully
    public function test_manager_can_reply_to_conversation(): void
    {
        $conv = $this->makeConversation();

        $response = $this->actingAs($this->manager(), 'tenant')
            ->withoutTenantMiddleware()
            ->postJson(route('tenant.manager.chat.reply', $conv), [
                'body' => 'Dzień dobry, chętnie pomogę!',
            ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('chat_messages', [
            'conversation_id' => $conv->id,
            'sender' => 'staff',
            'body' => 'Dzień dobry, chętnie pomogę!',
        ]);

        $conv->refresh();
        $this->assertNotNull($conv->last_message_at);
    }

    // CM04 – Reply body is required
    public function test_reply_requires_body(): void
    {
        $conv = $this->makeConversation();

        $response = $this->actingAs($this->manager(), 'tenant')
            ->withoutTenantMiddleware()
            ->postJson(route('tenant.manager.chat.reply', $conv), [
                'body' => '',
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['body']);
    }

    // CM05 – Reply body max 2000 chars
    public function test_reply_body_max_2000_characters(): void
    {
        $conv = $this->makeConversation();

        $response = $this->actingAs($this->manager(), 'tenant')
            ->withoutTenantMiddleware()
            ->postJson(route('tenant.manager.chat.reply', $conv), [
                'body' => str_repeat('a', 2001),
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['body']);
    }

    // CM06 – Manager closes conversation
    public function test_manager_can_close_conversation(): void
    {
        $conv = $this->makeConversation(['status' => 'open']);

        $response = $this->actingAs($this->manager(), 'tenant')
            ->withoutTenantMiddleware()
            ->patch(route('tenant.manager.chat.close', $conv));

        $response->assertRedirect();
        $this->assertEquals('closed', $conv->fresh()->status);
    }

    // CM07 – Manager polls messages
    public function test_manager_can_poll_messages(): void
    {
        $conv = $this->makeConversation();
        $this->addMessage($conv, 'customer', 'Pierwsza wiadomość');
        $this->addMessage($conv, 'staff', 'Odpowiedź managera');

        $response = $this->actingAs($this->manager(), 'tenant')
            ->withoutTenantMiddleware()
            ->getJson(route('tenant.manager.chat.poll', $conv));

        $response->assertStatus(200);
        $response->assertJsonCount(2);
        $response->assertJsonFragment(['sender' => 'customer']);
        $response->assertJsonFragment(['sender' => 'staff']);
    }

    // CM08 – Reply to closed conversation is refused (validation via model check)
    public function test_manager_cannot_reply_to_closed_conversation(): void
    {
        $conv = $this->makeConversation(['status' => 'closed']);

        $response = $this->actingAs($this->manager(), 'tenant')
            ->withoutTenantMiddleware()
            ->postJson(route('tenant.manager.chat.reply', $conv), [
                'body' => 'Wiadomość do zamkniętej rozmowy',
            ]);

        // Controller should return 422 or 403 for closed conversations
        $response->assertStatus(422);
    }

    // CM09 – Unauthenticated cannot access (bypass tenancy init but keep auth guard)
    public function test_unauthenticated_cannot_access_chat_manager(): void
    {
        $conv = $this->makeConversation();

        $bypassTenancy = [
            InitializeTenancyByDomain::class,
            PreventAccessFromCentralDomains::class,
            CheckSetupComplete::class,
            CheckTenantLicense::class,
            CheckRole::class,
            CheckPermission::class,
            // EnsureTenantAuth is intentionally NOT bypassed — we test it blocks the request
        ];

        $this->withoutMiddleware($bypassTenancy)
            ->get(route('tenant.manager.chat.index'))
            ->assertRedirect();

        $this->withoutMiddleware($bypassTenancy)
            ->get(route('tenant.manager.chat.show', $conv))
            ->assertRedirect();
    }
}
