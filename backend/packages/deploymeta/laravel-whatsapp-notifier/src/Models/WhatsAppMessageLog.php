<?php

namespace Deploymeta\WhatsAppNotifier\Models;

use Illuminate\Database\Eloquent\Model;

class WhatsAppMessageLog extends Model
{
    protected $table = 'whatsapp_message_logs';

    protected $fillable = [
        'channel',
        'to',
        'wa_message_id',
        'status',
        'message_type',
        'body',
        'request_payload',
        'response_payload',
        'error_message',
        'meta',
    ];

    protected $casts = [
        'request_payload' => 'array',
        'response_payload' => 'array',
        'meta' => 'array',
    ];
}
