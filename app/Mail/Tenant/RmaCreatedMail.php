<?php

namespace App\Mail\Tenant;

use App\Models\Tenant\Order;
use App\Models\Tenant\RmaRequest;
use App\Models\Tenant\Setting;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class RmaCreatedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public RmaRequest $rma,
        public Order $order
    ) {}

    public function envelope(): Envelope
    {
        $shopName = Setting::get('shop_name', config('app.name'));
        $fromAddress = Setting::get('smtp_from_address') ?: Setting::get('shop_email');
        $fromName = Setting::get('smtp_from_name') ?: $shopName;

        return new Envelope(
            from: $fromAddress ? new Address($fromAddress, $fromName) : null,
            subject: 'Zgłoszenie zwrotu ' . $this->rma->rma_number . ' – ' . $shopName,
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.tenant.rma-created');
    }
}
