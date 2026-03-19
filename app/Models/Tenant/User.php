<?php

namespace App\Models\Tenant;

use App\Notifications\StaffResetPasswordNotification;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'phone',
        'is_active',
        'two_factor_secret',
        'two_factor_enabled',
        'two_factor_recovery_codes',
        'two_factor_code',
        'two_factor_code_expires_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_secret',
        'two_factor_code',
        'two_factor_recovery_codes',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'two_factor_enabled' => 'boolean',
        'two_factor_recovery_codes' => 'array',
        'two_factor_code_expires_at' => 'datetime',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeRole($query, $role)
    {
        return $query->where('role', $role);
    }

    public function isManager(): bool
    {
        return $this->role === 'manager';
    }

    public function isChef(): bool
    {
        return $this->role === 'chef';
    }

    public function isWaiter(): bool
    {
        return $this->role === 'waiter';
    }

    public function isDriver(): bool
    {
        return $this->role === 'driver';
    }

    public function isFulfillment(): bool
    {
        return $this->role === 'fulfillment';
    }

    public function isWarehouse(): bool
    {
        return $this->role === 'warehouse';
    }

    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new StaffResetPasswordNotification($token));
    }
}
