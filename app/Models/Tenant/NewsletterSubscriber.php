<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;

class NewsletterSubscriber extends Model
{
    protected $fillable = [
        'email',
        'unsubscribed_at',
    ];

    protected $casts = [
        'unsubscribed_at' => 'datetime',
    ];

    public function unsubscribeToken(): string
    {
        return hash_hmac('sha256', $this->email . '|newsletter', config('app.key'));
    }
}
