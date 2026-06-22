<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Str;

class SupportMessageCreated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public string $tenantId,
        public int $ticketId,
        public string $subject,
        public string $authorType, // 'admin' or 'tenant'
        public string $messagePreview,
    ) {}

    public function broadcastOn(): array
    {
        $channels = [new PrivateChannel('support-admin')];

        // Only notify the tenant when admin replies
        if ($this->authorType === 'admin') {
            $channels[] = new PrivateChannel('support.' . $this->tenantId);
        }

        return $channels;
    }

    public function broadcastAs(): string
    {
        return 'support.message';
    }

    public function broadcastWith(): array
    {
        return [
            'ticket_id' => $this->ticketId,
            'subject' => $this->subject,
            'author_type' => $this->authorType,
            'message_preview' => Str::limit($this->messagePreview, 80),
            'tenant_id' => $this->tenantId,
        ];
    }
}
