<?php

namespace Deploymeta\WhatsAppNotifier\Http\Controllers;

use Deploymeta\WhatsAppNotifier\Events\WhatsAppWebhookReceived;
use Deploymeta\WhatsAppNotifier\Models\WhatsAppMessageLog;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;

class WebhookController extends Controller
{
    public function __invoke(Request $request)
    {
        if ($request->isMethod('GET')) {
            return $this->verify($request);
        }

        return $this->receive($request);
    }

    protected function verify(Request $request)
    {
        $verifyToken = $request->input('hub.verify_token')
            ?? $request->input('hub_verify_token');

        $challenge = $request->input('hub.challenge')
            ?? $request->input('hub_challenge');

        $mode = $request->input('hub.mode')
            ?? $request->input('hub_mode');

        $expectedToken = (string) config('whatsapp-notifier.webhook.verify_token');

        if ($mode === 'subscribe' && $expectedToken !== '' && hash_equals($expectedToken, (string) $verifyToken)) {
            return response($challenge, 200);
        }

        return response('', 403);
    }

    protected function receive(Request $request)
    {
        $payload = $request->all();

        if ((bool) config('whatsapp-notifier.log.enabled', true)) {
            WhatsAppMessageLog::create([
                'channel' => 'webhook',
                'status' => 'received',
                'message_type' => 'webhook',
                'request_payload' => config('whatsapp-notifier.log.keep_payload', true) ? $payload : null,
                'meta' => ['received_at' => now()->toISOString()],
            ]);
        }

        $this->applyStatusUpdates($payload);

        event(new WhatsAppWebhookReceived($payload));

        Log::info('WhatsApp webhook callback received.');

        return response()->json(['status' => 'ok']);
    }

    protected function applyStatusUpdates(array $payload): void
    {
        foreach ($payload['entry'] ?? [] as $entry) {
            foreach ($entry['changes'] ?? [] as $change) {
                foreach (($change['value']['statuses'] ?? []) as $statusItem) {
                    $waMessageId = data_get($statusItem, 'id');
                    $status = data_get($statusItem, 'status', 'unknown');

                    if (!$waMessageId) {
                        continue;
                    }

                    WhatsAppMessageLog::query()
                        ->where('wa_message_id', $waMessageId)
                        ->latest('id')
                        ->first()
                        ?->forceFill([
                            'status' => $status,
                            'response_payload' => config('whatsapp-notifier.log.keep_payload', true) ? $statusItem : null,
                        ])
                        ->save();
                }
            }
        }
    }
}
