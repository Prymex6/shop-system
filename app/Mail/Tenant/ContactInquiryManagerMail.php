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

class ContactInquiryManagerMail extends Mailable
{
    use Queueable, SendsInShopLanguage, SerializesModels;

    public function __construct(
        public string $senderName,
        public string $senderEmail,
        public string $senderMessage,
    ) {}

    public function envelope(): Envelope
    {
        $this->inShopLanguage();

        $shopName = Setting::get('shop_name', config('app.name'));
        $fromAddress = Setting::get('smtp_from_address') ?: config('mail.from.address');
        $fromName = Setting::get('smtp_from_name') ?: $shopName;

        return new Envelope(
            from: new Address($fromAddress, $fromName),
            replyTo: [new Address($this->senderEmail, $this->senderName)],
            subject: __('mail.subject_contact_inquiry_manager', ['shop' => $shopName]),
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.tenant.contact-inquiry-manager');
    }
}
