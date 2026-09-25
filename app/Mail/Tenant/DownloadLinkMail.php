<?php

namespace App\Mail\Tenant;

use App\Mail\Concerns\SendsInShopLanguage;
use App\Models\Tenant\Order;
use App\Models\Tenant\Setting;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;

class DownloadLinkMail extends Mailable
{
    use Queueable, SendsInShopLanguage, SerializesModels;

    public function __construct(
        public Order $order,
        public Collection $downloadLinks
    ) {}

    public function envelope(): Envelope
    {
        $this->inShopLanguage();

        $shopName = Setting::get('shop_name', config('app.name'));
        $fromAddress = Setting::get('smtp_from_address') ?: Setting::get('shop_email');
        $fromName = Setting::get('smtp_from_name') ?: $shopName;

        return new Envelope(
            from: $fromAddress ? new Address($fromAddress, $fromName) : null,
            subject: __('mail.subject_download_link', [
                'number' => $this->order->order_number,
                'shop' => $shopName,
            ]),
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.tenant.download-link');
    }
}
