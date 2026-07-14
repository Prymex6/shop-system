<?php

namespace App\Mail\Tenant;

use App\Models\Tenant\Order;
use App\Models\Tenant\Setting;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class NewOrderNotificationMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $adminUrl;

    public function __construct(public Order $order)
    {
        $this->adminUrl = route('tenant.manager.orders.index');
    }

    public function envelope(): Envelope
    {
        $shopName = Setting::get('shop_name', config('app.name'));
        $fromAddress = Setting::get('smtp_from_address') ?: Setting::get('shop_email');
        $fromName = Setting::get('smtp_from_name') ?: $shopName;

        return new Envelope(
            from: $fromAddress ? new Address($fromAddress, $fromName) : null,
            subject: '🔔 Nowe zamówienie #' . $this->order->order_number . ' – ' . $shopName,
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.tenant.new-order-notification');
    }
}
