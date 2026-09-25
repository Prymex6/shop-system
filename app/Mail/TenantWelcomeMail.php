<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TenantWelcomeMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $ownerName,
        public string $shopName,
        public string $loginUrl,
        public string $password,
        public string $email,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('mail.subject_tenant_welcome', [
                'app' => config('app.name'),
                'shop' => $this->shopName,
            ]),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.tenant-welcome',
        );
    }
}
