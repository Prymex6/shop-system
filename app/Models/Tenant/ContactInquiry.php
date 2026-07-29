<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;

class ContactInquiry extends Model
{
    protected $fillable = ['name', 'email', 'message', 'read'];

    protected $casts = [
        'read' => 'boolean',
    ];
}
