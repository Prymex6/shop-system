<?php

namespace App\Mail\Tenant;

use App\Http\Controllers\Tenant\Client\OrderTrackingController;
use App\Models\Tenant\Order;
use App\Models\Tenant\Setting;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OrderConfirmedMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $trackingUrl;

    public function __construct(public Order $order, ?string $trackingUrl = null)
    {
        $token = OrderTrackingController::generateToken($order->order_number);
        $this->trackingUrl = $trackingUrl ?? (url('/zamowienie/' . $order->order_number . '/sledzenie') . '?token=' . $token);
    }

    public function envelope(): Envelope
    {
        $shopName = Setting::get('shop_name', config('app.name'));
        $fromAddress = Setting::get('smtp_from_address') ?: Setting::get('shop_email');
        $fromName = Setting::get('smtp_from_name') ?: $shopName;

        return new Envelope(
            from: $fromAddress ? new Address($fromAddress, $fromName) : null,
            subject: 'Potwierdzenie zamówienia #' . $this->order->order_number . ' – ' . $shopName,
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.tenant.order-confirmed');
    }
}
