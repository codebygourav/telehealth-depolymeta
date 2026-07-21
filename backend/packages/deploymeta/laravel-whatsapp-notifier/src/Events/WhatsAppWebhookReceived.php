<?php

namespace Deploymeta\WhatsAppNotifier\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class WhatsAppWebhookReceived
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(public array $payload) {}
}
