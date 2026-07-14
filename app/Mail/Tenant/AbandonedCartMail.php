<?php

namespace App\Mail\Tenant;

use App\Models\Tenant\AbandonedCart;
use App\Models\Tenant\Setting;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AbandonedCartMail extends Mailable
{
    use Queueable, SerializesModels;

    public array $cartItems;

    public function __construct(public AbandonedCart $cart)
    {
        $this->cartItems = $cart->cart_data['items'] ?? [];
    }

    public function envelope(): Envelope
    {
        $shopName = Setting::get('shop_name', config('app.name'));
        $fromAddress = Setting::get('smtp_from_address') ?: Setting::get('shop_email');
        $fromName = Setting::get('smtp_from_name') ?: $shopName;

        return new Envelope(
            from: $fromAddress ? new Address($fromAddress, $fromName) : null,
            subject: 'Zapomniałeś czegoś w koszyku? – ' . $shopName,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.tenant.abandoned-cart',
            with: [
                'cartItems' => $this->cartItems,
            ],
        );
    }
}
