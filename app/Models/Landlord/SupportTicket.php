<?php

namespace App\Models\Landlord;

use Illuminate\Database\Eloquent\Model;

class SupportTicket extends Model
{
    protected $fillable = ['tenant_id', 'subject', 'status', 'priority', 'unread_by_admin'];

    protected $casts = ['unread_by_admin' => 'boolean'];

    public function messages()
    {
        return $this->hasMany(TicketMessage::class, 'ticket_id');
    }

    public function latestMessage()
    {
        return $this->hasOne(TicketMessage::class, 'ticket_id')->latestOfMany();
    }
}
