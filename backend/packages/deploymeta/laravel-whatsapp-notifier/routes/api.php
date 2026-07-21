<?php

use Deploymeta\WhatsAppNotifier\Http\Controllers\WebhookController;
use Illuminate\Support\Facades\Route;

Route::middleware(config('whatsapp-notifier.webhook.middleware', ['api']))
    ->match(['GET', 'POST'], config('whatsapp-notifier.webhook.path', 'api/v2/webhooks/whatsapp'), WebhookController::class)
    ->name('whatsapp-notifier.webhook');
