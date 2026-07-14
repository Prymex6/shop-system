<?php

namespace App\Mail\Tenant;

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
    use Queueable, SerializesModels;

    public function __construct(
        public Customer $customer,
        public string $newTier,
        public array $tierConfig
    ) {}

    public function envelope(): Envelope
    {
        $shopName = Setting::get('shop_name', config('app.name'));
        $fromAddress = Setting::get('shop_email');
        $fromName = $shopName;

        return new Envelope(
            from: $fromAddress ? new Address($fromAddress, $fromName) : null,
            subject: 'Awans w programie lojalnościowym – ' . ($this->tierConfig['name'] ?? $this->newTier) . '! – ' . $shopName,
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.tenant.loyalty-tier-upgrade');
    }
}
