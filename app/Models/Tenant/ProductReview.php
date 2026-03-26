<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductReview extends Model
{
    protected $fillable = [
        'product_id',
        'customer_id',
        'order_id',
        'rating',
        'title',
        'body',
        'images',
        'is_approved',
        'is_verified_purchase',
        'reply',
        'replied_at',
        'reviewer_name',
        'reviewer_email',
        'video_url',
    ];

    protected $casts = [
        'rating' => 'integer',
        'images' => 'array',
        'is_approved' => 'boolean',
        'is_verified_purchase' => 'boolean',
        'replied_at' => 'datetime',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function scopeApproved($query)
    {
        return $query->where('is_approved', true);
    }

    public function authorName(): string
    {
        return $this->reviewer_name ?? ($this->customer?->name ?? 'Anonimowy');
    }
}
