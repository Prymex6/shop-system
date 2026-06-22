<?php

namespace App\Events;

use App\Models\Tenant\ChatConversation;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Tenant-wide notification so a manager sees new chat activity (a new
 * conversation, or a new customer message) no matter which page they're on
 * — unlike ChatMessageSent, which only reaches whoever already has the
 * relevant conversation's Show.vue page open.
 */
class ChatActivityForManager implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly ChatConversation $conversation,
        public readonly string $preview,
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('chat-manager.' . tenant('id')),
        ];
    }

    public function broadcastAs(): string
    {
        return 'chat.activity';
    }

    public function broadcastWith(): array
    {
        return [
            'conversation_id' => $this->conversation->id,
            'guest_name' => $this->conversation->guest_name,
            'preview' => $this->preview,
        ];
    }
}
