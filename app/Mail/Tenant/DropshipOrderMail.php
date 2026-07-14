<?php

namespace App\Mail\Tenant;

use App\Models\Tenant\Order;
use App\Models\Tenant\PurchaseOrder;
use App\Models\Tenant\Supplier;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class DropshipOrderMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Order $order,
        public PurchaseOrder $purchaseOrder,
        public Supplier $supplier,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Nowe zamówienie dropship #{$this->order->order_number}",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.tenant.dropship-order',
        );
    }
}
