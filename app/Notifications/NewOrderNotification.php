<?php

namespace App\Notifications;

use App\Models\Tenant\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class NewOrderNotification extends Notification
{
    use Queueable;

    public function __construct(public Order $order) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'type' => 'new_order',
            'title' => __('messages.new_order'),
            'message' => __('messages.new_order_from', [
                'number' => $this->order->order_number,
                'customer' => $this->order->customer_name,
            ]),
            'order_id' => $this->order->id,
            'order_number' => $this->order->order_number,
            'total' => $this->order->total,
            'currency' => $this->order->currency,
            'url' => "/manager/orders/{$this->order->id}",
        ];
    }
}
