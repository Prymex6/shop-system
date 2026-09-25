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

class OrderStatusChangedMail extends Mailable
{
    use Queueable, SendsInShopLanguage, SerializesModels;

    public string $trackingUrl;

    public function __construct(
        public Order $order,
        public string $oldStatus,
        public string $newStatus,
        ?string $trackingUrl = null
    ) {
        $token = $order->tracking_token ?? OrderTrackingController::generateToken($order->order_number);
        $this->trackingUrl = $trackingUrl ?? (url('/zamowienie/' . $order->order_number . '/sledzenie') . '?token=' . $token);
    }

    public function envelope(): Envelope
    {
        $this->inShopLanguage();

        $shopName = Setting::get('shop_name', config('app.name'));
        // Spelled out rather than built from the status, so that a key which
        // stops existing is a thing the catalogue test can see.
        $label = match ($this->newStatus) {
            'pending' => __('mail.status_pending'),
            'awaiting_payment' => __('mail.status_awaiting_payment'),
            'paid' => __('mail.status_paid'),
            'completed' => __('mail.status_completed'),
            'cancelled' => __('mail.status_cancelled'),
            'refunded' => __('mail.status_refunded'),
            default => $this->newStatus,
        };
        $fromAddress = Setting::get('smtp_from_address') ?: Setting::get('shop_email');
        $fromName = Setting::get('smtp_from_name') ?: $shopName;

        return new Envelope(
            from: $fromAddress ? new Address($fromAddress, $fromName) : null,
            subject: __('mail.subject_order_status', [
                'number' => $this->order->order_number,
                'status' => $label,
                'shop' => $shopName,
            ]),
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.tenant.order-status-changed');
    }
}
