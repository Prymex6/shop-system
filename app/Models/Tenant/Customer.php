<?php

namespace App\Models\Tenant;

use App\Notifications\CustomerResetPasswordNotification;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class Customer extends Authenticatable
{
    use Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
        'delivery_address',
        'delivery_city',
        'delivery_postal_code',
        'google_id',
        'facebook_id',
        'avatar',
        'loyalty_points',
        'loyalty_points_earned_total',
        'loyalty_tier',
        'date_of_birth',
        'loyalty_birthday_rewarded_at',
        'loyalty_referred_by',
        'referral_code',
        'referral_bonus_awarded',
        'registration_ip',
        'marketing_opt_out',
        'pending_email',
        'email_change_token',
        'email_change_token_expires_at',
        'delete_requested_at',
    ];

    protected static function booted(): void
    {
        static::created(function (Customer $customer) {
            if (!$customer->referral_code) {
                $code = strtoupper(substr(md5($customer->id . $customer->email . uniqid()), 0, 8));
                $customer->updateQuietly(['referral_code' => $code]);
            }
        });
    }

    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new CustomerResetPasswordNotification($token));
    }

    public function unsubscribeToken(): string
    {
        return hash_hmac('sha256', $this->email . '|marketing', config('app.key'));
    }

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'password' => 'hashed',
        'loyalty_points' => 'integer',
        'loyalty_points_earned_total' => 'integer',
        'date_of_birth' => 'date',
        'loyalty_birthday_rewarded_at' => 'date',
        'marketing_opt_out' => 'boolean',
        'referral_bonus_awarded' => 'boolean',
        'email_change_token_expires_at' => 'datetime',
        'delete_requested_at' => 'datetime',
    ];

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function loyaltyPoints()
    {
        return $this->hasMany(LoyaltyPoint::class)->latest();
    }

    public function loyaltyRedemptions()
    {
        return $this->hasMany(LoyaltyRedemption::class)->latest();
    }

    public function referrer()
    {
        return $this->belongsTo(Customer::class, 'loyalty_referred_by');
    }

    public function badges(): BelongsToMany
    {
        return $this->belongsToMany(Badge::class, 'customer_badges')
            ->withPivot('awarded_at');
    }

    public function getTierNameAttribute(): string
    {
        return match ($this->loyalty_tier ?? 'bronze') {
            'silver' => __('messages.tier_silver'),
            'gold' => __('messages.tier_gold'),
            'platinum' => __('messages.tier_platinum'),
            'diamond' => __('messages.tier_diamond'),
            default => __('messages.tier_bronze'),
        };
    }

    public function getTierColorAttribute(): string
    {
        return match ($this->loyalty_tier ?? 'bronze') {
            'silver' => '#9ca3af',
            'gold' => '#f59e0b',
            'platinum' => '#60a5fa',
            'diamond' => '#a78bfa',
            default => '#cd7f32',
        };
    }
}
