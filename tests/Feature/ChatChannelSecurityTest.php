<?php

namespace Tests\Feature;

use App\Events\ChatActivityForManager;
use App\Events\ChatMessageSent;
use App\Models\Tenant\ChatConversation;
use App\Models\Tenant\User;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Support\Facades\Broadcast;
use Stancl\Tenancy\Contracts\Tenant;
use Tests\TenantTestCase;

/**
 * ChatMessageSent previously broadcast on a public `Channel` named only
 * `chat.{conversation_id}` — since conversation_id is a small per-tenant
 * sequential integer and all tenants share one Reverb app, any visitor on
 * any tenant's subdomain could open devtools and listen in on live
 * customer/staff chat across the whole platform. It must be a
 * PrivateChannel, tenant-scoped in the channel name itself (matching the
 * orders./support./staff-reports. pattern), and gated to manager-role
 * tenant users only.
 *
 * The authorization callback is invoked directly here (via the registered
 * channels.php closure) rather than through POST /broadcasting/auth — that
 * HTTP path has a pre-existing test-harness-only quirk affecting every
 * private channel in this app (confirmed by probing the already-correct
 * orders.{tenantId} channel, which fails the same way here), unrelated to
 * this fix. Direct closure invocation exercises the exact same logic that
 * ships to production without depending on that unrelated routing wrinkle.
 */
class ChatChannelSecurityTest extends TenantTestCase
{
    public function test_chat_message_broadcasts_on_tenant_scoped_private_channel(): void
    {
        $conversation = ChatConversation::create([
            'access_token' => 'token',
            'guest_name' => 'Guest',
            'guest_email' => 'guest@test.com',
            'status' => 'open',
            'last_message_at' => now(),
        ]);

        $message = $conversation->messages()->create([
            'sender' => 'customer',
            'body' => 'Hello',
        ]);

        $event = new ChatMessageSent($message);
        $channels = $event->broadcastOn();

        $this->assertCount(1, $channels);
        $this->assertInstanceOf(PrivateChannel::class, $channels[0]);
        $this->assertStringStartsWith('private-chat.', $channels[0]->name);
        $this->assertStringEndsWith('.' . $conversation->id, $channels[0]->name);
    }

    private function chatChannelCallback(): \Closure
    {
        return $this->channelCallback('chat.{tenantId}.{conversationId}');
    }

    private function channelCallback(string $pattern): \Closure
    {
        require base_path('routes/channels.php');

        foreach (Broadcast::getChannels() as $registeredPattern => $callback) {
            if ($registeredPattern === $pattern) {
                return $callback;
            }
        }

        $this->fail("{$pattern} channel is not registered in routes/channels.php");
    }

    private function bindFakeTenant(string $id): void
    {
        $fakeTenant = new class($id)
        {
            public function __construct(private string $id) {}

            public function getAttribute($key)
            {
                return $key === 'id' ? $this->id : null;
            }
        };
        $this->app->instance(Tenant::class, $fakeTenant);
    }

    public function test_manager_of_owning_tenant_is_authorized(): void
    {
        $this->bindFakeTenant('tenant-a');
        $manager = User::create([
            'name' => 'Manager', 'email' => 'mgr@test.com',
            'password' => bcrypt('secret'), 'role' => 'manager', 'is_active' => true,
        ]);

        $callback = $this->chatChannelCallback();

        $this->assertTrue((bool) $callback($manager, 'tenant-a', 1));
    }

    public function test_non_manager_staff_is_denied(): void
    {
        $this->bindFakeTenant('tenant-a');
        $staff = User::create([
            'name' => 'Fulfillment', 'email' => 'staff@test.com',
            'password' => bcrypt('secret'), 'role' => 'fulfillment', 'is_active' => true,
        ]);

        $callback = $this->chatChannelCallback();

        $this->assertFalse((bool) $callback($staff, 'tenant-a', 1));
    }

    public function test_manager_of_a_different_tenant_is_denied(): void
    {
        // The subscribing user resolved as tenant-a's manager, but the
        // channel name itself claims to belong to a different tenant —
        // this is exactly the cross-tenant collision the fix guards against.
        $this->bindFakeTenant('tenant-a');
        $manager = User::create([
            'name' => 'Manager', 'email' => 'mgr@test.com',
            'password' => bcrypt('secret'), 'role' => 'manager', 'is_active' => true,
        ]);

        $callback = $this->chatChannelCallback();

        $this->assertFalse((bool) $callback($manager, 'tenant-b', 1));
    }

    public function test_unauthenticated_user_is_denied(): void
    {
        $this->bindFakeTenant('tenant-a');
        $callback = $this->chatChannelCallback();

        $this->assertFalse((bool) $callback(null, 'tenant-a', 1));
    }

    public function test_chat_activity_broadcasts_on_tenant_scoped_private_channel(): void
    {
        $this->bindFakeTenant('tenant-a');
        $conversation = ChatConversation::create([
            'access_token' => 'token',
            'guest_name' => 'Guest',
            'guest_email' => 'guest@test.com',
            'status' => 'open',
            'last_message_at' => now(),
        ]);

        $event = new ChatActivityForManager($conversation, 'Nowa rozmowa');
        $channels = $event->broadcastOn();

        $this->assertCount(1, $channels);
        $this->assertInstanceOf(PrivateChannel::class, $channels[0]);
        $this->assertSame('private-chat-manager.tenant-a', $channels[0]->name);
    }

    public function test_manager_is_authorized_on_chat_manager_channel(): void
    {
        $this->bindFakeTenant('tenant-a');
        $manager = User::create([
            'name' => 'Manager', 'email' => 'mgr2@test.com',
            'password' => bcrypt('secret'), 'role' => 'manager', 'is_active' => true,
        ]);

        $callback = $this->channelCallback('chat-manager.{tenantId}');

        $this->assertTrue((bool) $callback($manager, 'tenant-a'));
    }

    public function test_non_manager_is_denied_on_chat_manager_channel(): void
    {
        $this->bindFakeTenant('tenant-a');
        $staff = User::create([
            'name' => 'Fulfillment', 'email' => 'staff2@test.com',
            'password' => bcrypt('secret'), 'role' => 'fulfillment', 'is_active' => true,
        ]);

        $callback = $this->channelCallback('chat-manager.{tenantId}');

        $this->assertFalse((bool) $callback($staff, 'tenant-a'));
    }
}
