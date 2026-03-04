<?php

namespace App\Models\Landlord;

use Illuminate\Database\Eloquent\Model;

class ContactInquiry extends Model
{
    protected $fillable = ['name', 'email', 'phone', 'subject', 'message', 'read'];

    protected $casts = [
        'read' => 'boolean',
    ];
}
