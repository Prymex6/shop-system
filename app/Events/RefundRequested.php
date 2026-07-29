<?php

namespace App\Events;

use App\Models\Tenant\Refund;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class RefundRequested implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public Refund $refund) {}

    public function broadcastOn(): array
    {
        return [new PrivateChannel('orders.' . tenant('id'))];
    }

    public function broadcastAs(): string
    {
        return 'refund.requested';
    }

    public function broadcastWith(): array
    {
        return [
            'refund_id' => $this->refund->id,
            'order_number' => $this->refund->order->order_number ?? null,
            'amount' => $this->refund->amount,
        ];
    }
}
