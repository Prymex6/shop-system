<?php

namespace App\Mail\Tenant;

use App\Models\Tenant\Setting;
use App\Models\Tenant\Wishlist;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class WishlistAlertMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @param 'price_drop'|'restock' $reason
     */
    public function __construct(public Wishlist $item, public string $reason) {}

    public function envelope(): Envelope
    {
        $shopName = Setting::get('shop_name', config('app.name'));
        $fromAddress = Setting::get('smtp_from_address') ?: Setting::get('shop_email');
        $fromName = Setting::get('smtp_from_name') ?: $shopName;

        $subject = $this->reason === 'price_drop'
            ? 'Cena spadła! – ' . $this->item->product->name
            : 'Znowu dostępny! – ' . $this->item->product->name;

        return new Envelope(
            from: $fromAddress ? new Address($fromAddress, $fromName) : null,
            subject: $subject,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.tenant.wishlist-alert',
            with: [
                'product' => $this->item->product,
                'reason' => $this->reason,
                'oldPrice' => $this->item->price_at_added,
            ],
        );
    }
}
