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
            'title' => 'Niski stan magazynowy',
            'message' => "Produkt \"{$this->product->name}\" ma tylko {$this->stockQty} szt. na stanie",
            'product_id' => $this->product->id,
            'stock_qty' => $this->stockQty,
            'url' => '/manager/inventory',
        ];
    }
}
