<?php

namespace App\Mail\Tenant;

use App\Mail\Concerns\SendsInShopLanguage;
use App\Models\Tenant\Setting;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class EmailChangeMail extends Mailable
{
    use Queueable, SendsInShopLanguage, SerializesModels;

    public function __construct(
        public readonly string $verifyUrl,
    ) {}

    public function envelope(): Envelope
    {
        $this->inShopLanguage();

        $from = Setting::get('smtp_from_address') ?: config('mail.from.address');
        $fromName = Setting::get('smtp_from_name') ?: Setting::get('shop_name', config('app.name'));

        return new Envelope(
            from: new Address($from, $fromName),
            subject: __('mail.subject_email_change'),
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.tenant.email-change');
    }
}
