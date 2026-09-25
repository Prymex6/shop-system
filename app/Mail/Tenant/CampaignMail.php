<?php

namespace App\Mail\Tenant;

use App\Mail\Concerns\SendsInShopLanguage;
use App\Models\Tenant\EmailCampaign;
use App\Models\Tenant\Setting;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CampaignMail extends Mailable
{
    use Queueable, SendsInShopLanguage, SerializesModels;

    /**
     * Recipient is passed as plain values, not a Customer model, so this
     * mailable also serves newsletter-only subscribers (NewsletterSubscriber
     * has no `name` and isn't a Customer at all).
     */
    public function __construct(
        public EmailCampaign $campaign,
        public string $recipientEmail,
        public ?string $recipientName,
        public string $unsubscribeUrl,
    ) {}

    public function envelope(): Envelope
    {
        $this->inShopLanguage();

        $fromAddress = Setting::get('smtp_from_address') ?: Setting::get('shop_email');
        $fromName = Setting::get('smtp_from_name') ?: Setting::get('shop_name', config('app.name'));

        return new Envelope(
            from: $fromAddress ? new Address($fromAddress, $fromName) : null,
            subject: $this->campaign->subject,
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.tenant.campaign');
    }
}
