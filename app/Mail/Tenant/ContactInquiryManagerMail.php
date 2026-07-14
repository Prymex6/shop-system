<?php

namespace App\Mail\Tenant;

use App\Models\Tenant\Setting;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ContactInquiryManagerMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $senderName,
        public string $senderEmail,
        public string $senderMessage,
    ) {}

    public function envelope(): Envelope
    {
        $shopName = Setting::get('shop_name', config('app.name'));
        $fromAddress = Setting::get('smtp_from_address') ?: config('mail.from.address');
        $fromName = Setting::get('smtp_from_name') ?: $shopName;

        return new Envelope(
            from: new Address($fromAddress, $fromName),
            replyTo: [new Address($this->senderEmail, $this->senderName)],
            subject: 'Nowa wiadomość ze strony kontaktowej – ' . $shopName,
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.tenant.contact-inquiry-manager');
    }
}
