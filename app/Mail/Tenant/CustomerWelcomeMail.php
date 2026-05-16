<?php

namespace App\Mail\Tenant;

use App\Models\Tenant\Setting;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CustomerWelcomeMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly string $customerName,
        public readonly string $shopName,
    ) {}

    public function envelope(): Envelope
    {
        $from = Setting::get('smtp_from_address') ?: Setting::get('shop_email') ?: 'noreply@example.com';
        $fromName = Setting::get('smtp_from_name') ?: Setting::get('shop_name') ?: 'Sklep';

        return new Envelope(
            from: new Address($from, $fromName),
            subject: 'Witamy w ' . $this->shopName . '!',
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.tenant.customer-welcome');
    }
}
