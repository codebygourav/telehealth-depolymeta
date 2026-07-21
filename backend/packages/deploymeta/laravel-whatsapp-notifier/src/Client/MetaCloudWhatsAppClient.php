<?php

namespace Deploymeta\WhatsAppNotifier\Client;

use Deploymeta\WhatsAppNotifier\Messages\WhatsAppMessage;
use Deploymeta\WhatsAppNotifier\Models\WhatsAppMessageLog;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class MetaCloudWhatsAppClient
{
    public function send(string $to, WhatsAppMessage $message): array
    {
        $token = (string) config('whatsapp-notifier.access_token');
        $phoneNumberId = (string) config('whatsapp-notifier.phone_number_id');
        $apiVersion = (string) config('whatsapp-notifier.api_version', 'v23.0');
        $baseUrl = rtrim((string) config('whatsapp-notifier.base_url', 'https://graph.facebook.com'), '/');

        if ($token === '' || $phoneNumberId === '') {
            throw new RuntimeException('WhatsApp is not configured. Missing access token or phone number id.');
        }

        $payload = [
            'messaging_product' => 'whatsapp',
            'to' => $to,
            'recipient_type' => 'individual',
        ];

        if ($message->type === 'template') {
            $payload['type'] = 'template';
            $payload['template'] = [
                'name' => (string) $message->templateName,
                'language' => ['code' => (string) $message->templateLanguage],
                'components' => $message->templateComponents,
            ];
        } else {
            $payload['type'] = 'text';
            $payload['text'] = [
                'preview_url' => false,
                'body' => (string) $message->text,
            ];
        }

        $log = $this->createLog(
            channel: 'outbound',
            to: $to,
            messageType: $payload['type'],
            body: $message->type === 'template' ? null : ($message->text ?? null),
            requestPayload: config('whatsapp-notifier.log.keep_payload', true) ? $payload : null,
            status: 'pending',
            meta: $message->meta,
        );

        try {
            $response = Http::withToken($token)
                ->acceptJson()
                ->post("{$baseUrl}/{$apiVersion}/{$phoneNumberId}/messages", $payload);

            $responseData = $response->json() ?? [];

            if (!$response->successful()) {
                $errorMessage = data_get($responseData, 'error.message', 'Unknown error from Meta API.');
                $this->updateLog(
                    $log,
                    status: 'failed',
                    responsePayload: config('whatsapp-notifier.log.keep_payload', true) ? $responseData : null,
                    errorMessage: $errorMessage,
                );

                throw new RuntimeException('Meta WhatsApp API error: ' . $errorMessage);
            }

            $waMessageId = data_get($responseData, 'messages.0.id');

            $this->updateLog(
                $log,
                status: 'sent',
                responsePayload: config('whatsapp-notifier.log.keep_payload', true) ? $responseData : null,
                waMessageId: $waMessageId,
            );

            return $responseData;
        } catch (\Throwable $exception) {
            $this->updateLog(
                $log,
                status: 'failed',
                errorMessage: $exception->getMessage(),
            );

            Log::error('WhatsApp delivery failed.', [
                'to' => $to,
                'message' => $exception->getMessage(),
            ]);

            throw $exception;
        }
    }

    public function normalizePhoneNumber(string $phone): string
    {
        $clean = preg_replace('/[^0-9+]/', '', trim($phone)) ?? '';

        if ($clean === '') {
            return '';
        }

        if (str_starts_with($clean, '+')) {
            return ltrim($clean, '+');
        }

        $defaultCountryCode = preg_replace('/[^0-9]/', '', (string) config('whatsapp-notifier.default_country_code', ''));

        if ($defaultCountryCode !== '' && strlen($clean) <= 10) {
            return $defaultCountryCode . $clean;
        }

        return $clean;
    }

    protected function createLog(
        string $channel,
        ?string $to,
        ?string $messageType,
        ?string $body,
        ?array $requestPayload,
        string $status,
        array $meta = [],
    ): ?WhatsAppMessageLog {
        if (!(bool) config('whatsapp-notifier.log.enabled', true)) {
            return null;
        }

        return WhatsAppMessageLog::create([
            'channel' => $channel,
            'to' => $to,
            'message_type' => $messageType,
            'body' => $body,
            'request_payload' => $requestPayload,
            'status' => $status,
            'meta' => $meta,
        ]);
    }

    protected function updateLog(
        ?WhatsAppMessageLog $log,
        string $status,
        ?array $responsePayload = null,
        ?string $errorMessage = null,
        ?string $waMessageId = null,
    ): void {
        if (!$log) {
            return;
        }

        $log->forceFill([
            'status' => $status,
            'response_payload' => $responsePayload ?? $log->response_payload,
            'error_message' => $errorMessage,
            'wa_message_id' => $waMessageId ?? $log->wa_message_id,
        ])->save();
    }
}
