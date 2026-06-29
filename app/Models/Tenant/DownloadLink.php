<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DownloadLink extends Model
{
    protected $fillable = [
        'order_id',
        'order_item_id',
        'product_id',
        'file_id',
        'token',
        'expires_at',
        'downloads_count',
        'download_limit',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'downloads_count' => 'integer',
        'download_limit' => 'integer',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function orderItem(): BelongsTo
    {
        return $this->belongsTo(OrderItem::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function file(): BelongsTo
    {
        return $this->belongsTo(DigitalFile::class, 'file_id');
    }

    public function isValid(): bool
    {
        if ($this->expires_at && $this->expires_at->isPast()) {
            return false;
        }
        if ($this->download_limit && $this->downloads_count >= $this->download_limit) {
            return false;
        }

        return true;
    }

    public function incrementDownloads(): void
    {
        $this->increment('downloads_count');
    }

    public function remainingDownloads(): ?int
    {
        if (!$this->download_limit) {
            return null;
        }

        return max(0, $this->download_limit - $this->downloads_count);
    }
}
