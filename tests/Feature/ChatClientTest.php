<?php

namespace Tests\Feature;

use App\Events\ChatActivityForManager;
use App\Models\Tenant\ChatConversation;
use App\Models\Tenant\ChatMessage;
use App\Models\Tenant\Customer;
use Illuminate\Support\Facades\Event;
use Tests\TenantTestCase;

/**
 * Tests for the client-side Live Chat widget (ChatController).
 *
 * Covers:
 *   CC01 – Guest can start a new conversation
 *   CC02 – start() requires name
 *   CC03 – start() requires valid email
 *   CC04 – Logged-in customer can start conversation (customer_id set)
 *   CC05 – Client can send a message to an open conversation
 *   CC06 – send() requires non-empty body
 *   CC07 – send() body max 2000 characters
 *   CC08 – Client cannot send to a closed conversation (403)
 *   CC09 – Client can poll messages (staff messages marked as read)
 *   CC10 – Poll returns messages in chronological order
 */
class ChatClientTest extends TenantTestCase
{
    private function customer(): Customer
    {
        return Customer::create([
            'name' => 'Klient Testowy',
            'email' => 'klient@test.com',
            'password' => bcrypt('s'),
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

    // CC01 – Guest starts a new conversation
    public function test_guest_can_start_conversation(): void
    {
        $response = $this->withoutTenantMiddleware()
            ->postJson(route('tenant.chat.start'), [
                'name' => 'Jan Kowalski',
                'email' => 'jan@test.com',
            ]);

        $response->assertStatus(200);
        $response->assertJsonStructure(['conversation_id']);

        $this->assertDatabaseHas('chat_conversations', [
            'guest_name' => 'Jan Kowalski',
            'guest_email' => 'jan@test.com',
            'status' => 'open',
        ]);
    }

    // Manager gets a tenant-wide notification event when a guest starts a new chat
    public function test_starting_conversation_broadcasts_manager_notification(): void
    {
        Event::fake([ChatActivityForManager::class]);

        $this->withoutTenantMiddleware()
            ->postJson(route('tenant.chat.start'), [
                'name' => 'Jan Kowalski',
                'email' => 'jan@test.com',
            ]);

        Event::assertDispatched(ChatActivityForManager::class);
    }

    // Manager gets a tenant-wide notification event when a guest sends a message
    public function test_sending_message_broadcasts_manager_notification(): void
    {
        Event::fake([ChatActivityForManager::class]);
        $conv = $this->makeConversation();

        $this->withoutTenantMiddleware()
            ->postJson(route('tenant.chat.send', $conv), [
                'body' => 'Dzień dobry',
            ]);

        Event::assertDispatched(ChatActivityForManager::class, function ($event) use ($conv) {
            return $event->conversation->id === $conv->id && $event->preview === 'Dzień dobry';
        });
    }

    // CC02 – start() requires name
    public function test_start_requires_name(): void
    {
        $response = $this->withoutTenantMiddleware()
            ->postJson(route('tenant.chat.start'), [
                'email' => 'jan@test.com',
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['name']);
    }

    // CC03 – start() requires valid email
    public function test_start_requires_valid_email(): void
    {
        $response = $this->withoutTenantMiddleware()
            ->postJson(route('tenant.chat.start'), [
                'name' => 'Jan',
                'email' => 'niepoprawny-email',
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['email']);
    }

    // Honeypot – filled "website" field silently no-ops instead of creating a conversation
    public function test_honeypot_field_silently_blocks_start(): void
    {
        $response = $this->withoutTenantMiddleware()
            ->postJson(route('tenant.chat.start'), [
                'name' => 'Bot',
                'email' => 'bot@test.com',
                'website' => 'http://spam.example',
            ]);

        $response->assertStatus(200);
        $response->assertJson(['conversation_id' => 0]);
        $this->assertDatabaseMissing('chat_conversations', ['guest_email' => 'bot@test.com']);
    }

    // CC04 – Logged-in customer gets customer_id set on conversation
    public function test_logged_in_customer_start_sets_customer_id(): void
    {
        $customer = $this->customer();

        $response = $this->actingAs($customer, 'customer')
            ->withoutTenantMiddleware()
            ->postJson(route('tenant.chat.start'), [
                'name' => $customer->name,
                'email' => $customer->email,
            ]);

        $response->assertStatus(200);
        $convId = $response->json('conversation_id');

        $this->assertDatabaseHas('chat_conversations', [
            'id' => $convId,
            'customer_id' => $customer->id,
        ]);
    }

    // CC05 – Client sends a message to open conversation
    public function test_client_can_send_message(): void
    {
        $conv = $this->makeConversation();

        $response = $this->withoutTenantMiddleware()
            ->postJson(route('tenant.chat.send', $conv), [
                'body' => 'Dzień dobry, mam pytanie o zamówienie.',
            ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('chat_messages', [
            'conversation_id' => $conv->id,
            'sender' => 'customer',
            'body' => 'Dzień dobry, mam pytanie o zamówienie.',
        ]);

        $conv->refresh();
        $this->assertNotNull($conv->last_message_at);
    }

    // CC06 – send() requires body
    public function test_send_requires_body(): void
    {
        $conv = $this->makeConversation();

        $response = $this->withoutTenantMiddleware()
            ->postJson(route('tenant.chat.send', $conv), [
                'body' => '',
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['body']);
    }

    // CC07 – send() body max 2000 characters
    public function test_send_body_max_2000_characters(): void
    {
        $conv = $this->makeConversation();

        $response = $this->withoutTenantMiddleware()
            ->postJson(route('tenant.chat.send', $conv), [
                'body' => str_repeat('x', 2001),
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['body']);
    }

    // CC08 – Client cannot send to closed conversation (403)
    public function test_client_cannot_send_to_closed_conversation(): void
    {
        $conv = $this->makeConversation(['status' => 'closed']);

        $response = $this->withoutTenantMiddleware()
            ->postJson(route('tenant.chat.send', $conv), [
                'body' => 'Próbuję pisać do zamkniętej rozmowy',
            ]);

        $response->assertStatus(403);
    }

    // CC09 – Client polls messages, staff messages marked as read
    public function test_client_can_poll_messages_and_staff_messages_marked_read(): void
    {
        $conv = $this->makeConversation();
        $this->addMessage($conv, 'customer', 'Moje pytanie');
        $staffMsg = $this->addMessage($conv, 'staff', 'Odpowiedź managera');
        $this->assertNull($staffMsg->read_at);

        $response = $this->withoutTenantMiddleware()
            ->getJson(route('tenant.chat.poll', $conv));

        $response->assertStatus(200);
        $response->assertJsonCount(2);

        $this->assertNotNull($staffMsg->fresh()->read_at);
    }

    // Poll route is throttled like its start/send siblings (30 requests/60min)
    public function test_poll_route_is_rate_limited(): void
    {
        $conv = $this->makeConversation();

        for ($i = 0; $i < 30; $i++) {
            $this->withoutTenantMiddleware()->getJson(route('tenant.chat.poll', $conv))->assertStatus(200);
        }

        $this->withoutTenantMiddleware()
            ->getJson(route('tenant.chat.poll', $conv))
            ->assertStatus(429);
    }

    // CC10 – Poll returns messages in chronological order
    public function test_poll_returns_messages_in_chronological_order(): void
    {
        $conv = $this->makeConversation();
        $this->addMessage($conv, 'customer', 'Pierwsza');
        $this->addMessage($conv, 'staff', 'Druga');
        $this->addMessage($conv, 'customer', 'Trzecia');

        $response = $this->withoutTenantMiddleware()
            ->getJson(route('tenant.chat.poll', $conv));

        $response->assertStatus(200);
        $data = $response->json();

        $this->assertEquals('Pierwsza', $data[0]['body']);
        $this->assertEquals('Druga', $data[1]['body']);
        $this->assertEquals('Trzecia', $data[2]['body']);
    }
}
