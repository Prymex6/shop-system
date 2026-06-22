<?php

namespace App\Events;

use App\Models\Tenant\ChatMessage;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ChatMessageSent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public readonly ChatMessage $message) {}

    public function broadcastOn(): array
    {
        // Tenant-scoped like orders./support./staff-reports. — the numeric
        // conversation_id alone would collide across tenants sharing one
        // Reverb app, and this must be a PrivateChannel (not a public one)
        // so only authenticated staff of the owning tenant can subscribe.
        return [
            new PrivateChannel('chat.' . tenant('id') . '.' . $this->message->conversation_id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'message.sent';
    }

    public function broadcastWith(): array
    {
        return [
            'id' => $this->message->id,
            'conversation_id' => $this->message->conversation_id,
            'sender' => $this->message->sender,
            'body' => $this->message->body,
            'created_at' => $this->message->created_at,
        ];
    }
}
