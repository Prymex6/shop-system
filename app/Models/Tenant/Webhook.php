<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Webhook extends Model
{
    protected $fillable = [
        'name',
        'url',
        'events',
        'secret',
        'is_active',
        'last_triggered_at',
        'failure_count',
    ];

    protected $casts = [
        'events' => 'array',
        'is_active' => 'boolean',
        'last_triggered_at' => 'datetime',
        'failure_count' => 'integer',
    ];

    // The signing secret proves authenticity to the receiving endpoint —
    // it must never round-trip to the browser once set. Only a masked hint
    // (last 4 chars) is exposed, via the secret_hint accessor below.
    protected $hidden = ['secret'];

    protected $appends = ['secret_hint'];

    public function getSecretHintAttribute(): ?string
    {
        return $this->secret ? '••••' . substr($this->secret, -4) : null;
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForEvent($query, string $event)
    {
        return $query->whereJsonContains('events', $event);
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($webhook) {
            if (empty($webhook->secret)) {
                $webhook->secret = Str::random(32);
            }
        });
    }
}
