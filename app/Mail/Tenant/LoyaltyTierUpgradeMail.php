<?php

namespace App\Mail\Tenant;

use App\Mail\Concerns\SendsInShopLanguage;
use App\Models\Tenant\Customer;
use App\Models\Tenant\Setting;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class LoyaltyTierUpgradeMail extends Mailable
{
    use Queueable, SendsInShopLanguage, SerializesModels;

    public function __construct(
        public Customer $customer,
        public string $newTier,
        public array $tierConfig
    ) {}

    public function envelope(): Envelope
    {
        $this->inShopLanguage();

        $shopName = Setting::get('shop_name', config('app.name'));
        $fromAddress = Setting::get('shop_email');
        $fromName = $shopName;

        return new Envelope(
            from: $fromAddress ? new Address($fromAddress, $fromName) : null,
            subject: __('mail.subject_tier_upgrade', [
                'tier' => $this->tierConfig['name'] ?? $this->newTier,
                'shop' => $shopName,
            ]),
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.tenant.loyalty-tier-upgrade');
    }
}
