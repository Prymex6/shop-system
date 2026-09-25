<?php

namespace App\Notifications;

use App\Models\Tenant\Product;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class LowStockNotification extends Notification
{
    use Queueable;

    public function __construct(public Product $product, public int $stockQty) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'type' => 'low_stock',
            'title' => __('messages.low_stock_title'),
            'message' => __('messages.low_stock_body', [
                'product' => $this->product->name,
                'count' => $this->stockQty,
            ]),
            'product_id' => $this->product->id,
            'stock_qty' => $this->stockQty,
            'url' => '/manager/inventory',
        ];
    }
}
