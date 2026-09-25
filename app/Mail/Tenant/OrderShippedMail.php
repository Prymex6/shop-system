<?php

namespace App\Mail\Tenant;

use App\Http\Controllers\Tenant\Client\OrderTrackingController;
use App\Mail\Concerns\SendsInShopLanguage;
use App\Models\Tenant\Order;
use App\Models\Tenant\Setting;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OrderShippedMail extends Mailable
{
    use Queueable, SendsInShopLanguage, SerializesModels;

    public string $trackingUrl;

    public function __construct(public Order $order, ?string $trackingUrl = null)
    {
        $token = $order->tracking_token
            ?? OrderTrackingController::generateToken($order->order_number);

        $this->trackingUrl = $trackingUrl
            ?? (url('/zamowienie/' . $order->order_number . '/sledzenie') . '?token=' . $token);
    }

    public function envelope(): Envelope
    {
        $this->inShopLanguage();

        $shopName = Setting::get('shop_name', config('app.name'));
        $fromAddress = Setting::get('smtp_from_address') ?: Setting::get('shop_email');
        $fromName = Setting::get('smtp_from_name') ?: $shopName;

        return new Envelope(
            from: $fromAddress ? new Address($fromAddress, $fromName) : null,
            subject: __('mail.subject_order_shipped', ['number' => $this->order->order_number, 'shop' => $shopName]),
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.tenant.order-shipped');
    }
}
