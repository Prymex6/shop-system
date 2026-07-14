<?php

namespace App\Mail\Tenant;

use App\Models\Tenant\Product;
use App\Models\Tenant\ProductVariant;
use App\Models\Tenant\Setting;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class LowStockAlertMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Product $product,
        public ?ProductVariant $variant = null,
        public int $currentStock = 0
    ) {}

    public function envelope(): Envelope
    {
        $shopName = Setting::get('shop_name', config('app.name'));
        $fromAddress = Setting::get('smtp_from_address') ?: Setting::get('shop_email');
        $fromName = Setting::get('smtp_from_name') ?: $shopName;

        return new Envelope(
            from: $fromAddress ? new Address($fromAddress, $fromName) : null,
            to: $fromAddress ? [new Address($fromAddress, $shopName)] : [],
            subject: '⚠️ Niski stan magazynowy: ' . $this->product->name . ' | ' . $shopName,
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.tenant.low-stock-alert');
    }
}
