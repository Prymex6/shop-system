<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;

class FraudBlocklist extends Model
{
    protected $table = 'fraud_blocklist';

    protected $fillable = [
        'type',
        'value',
        'reason',
    ];

    // type: 'email' | 'ip' | 'card_bin'
}
