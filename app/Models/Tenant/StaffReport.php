<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StaffReport extends Model
{
    protected $table = 'staff_reports';

    protected $fillable = [
        'staff_user_id',
        'role',
        'title',
        'message',
        'status',
    ];

    public function staffUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'staff_user_id');
    }

    public function scopeNew($query)
    {
        return $query->where('status', 'new');
    }
}
