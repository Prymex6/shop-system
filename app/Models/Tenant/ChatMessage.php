<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;

class ChatMessage extends Model
{
    protected $fillable = ['conversation_id', 'sender', 'body', 'read_at'];

    protected $casts = ['read_at' => 'datetime'];
}
