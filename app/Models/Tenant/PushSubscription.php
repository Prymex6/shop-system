<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;

class PushSubscription extends Model
{
    protected $fillable = [
        'staff_user_id',
        'endpoint',
        'p256dh_key',
        'auth_key',
        'expires_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
    ];

    public function staffUser()
    {
        return $this->belongsTo(User::class, 'staff_user_id');
    }
}
