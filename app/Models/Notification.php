<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    protected $fillable = [
        'service_request_id',
        'recipients',
        'subject',
        'template',
        'large_content',
        'short_message',
        'status',
        'logs',
        'sent_at',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
    ];
}