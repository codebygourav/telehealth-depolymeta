<?php

namespace Deploymeta\WhatsAppNotifier\Channels;

use Deploymeta\WhatsAppNotifier\Client\MetaCloudWhatsAppClient;
use Deploymeta\WhatsAppNotifier\Messages\WhatsAppMessage;
use Illuminate\Notifications\Notification;
use RuntimeException;

class WhatsAppChannel
{
    public function __construct(protected MetaCloudWhatsAppClient $client) {}

    public function send(mixed $notifiable, Notification $notification): void
    {
        if (!(bool) config('whatsapp-notifier.enabled', true)) {
            return;
        }

        $to = $this->resolveRecipient($notifiable, $notification);

        if (!$to) {
            return;
        }

        $message = $this->resolveMessage($notification, $notifiable);

        $normalizedTo = $this->client->normalizePhoneNumber($to);

        if ($normalizedTo === '') {
            throw new RuntimeException('Invalid WhatsApp recipient phone number.');
        }

        $this->client->send($normalizedTo, $message);
    }

    protected function resolveRecipient(mixed $notifiable, Notification $notification): ?string
    {
        $route = method_exists($notifiable, 'routeNotificationFor')
            ? $notifiable->routeNotificationFor('whatsapp', $notification)
            : null;

        $phone = $route ?: data_get($notifiable, 'phone');

        return is_string($phone) ? $phone : null;
    }

    protected function resolveMessage(Notification $notification, mixed $notifiable): WhatsAppMessage
    {
        if (method_exists($notification, 'toWhatsApp')) {
            $message = $notification->toWhatsApp($notifiable);

            if ($message instanceof WhatsAppMessage) {
                return $message;
            }
        }

        $array = method_exists($notification, 'toArray') ? (array) $notification->toArray($notifiable) : [];
        $fallbackMessage = (string) (data_get($array, 'message') ?: data_get($array, 'title') ?: 'You have a new notification.');

        return WhatsAppMessage::text($fallbackMessage, [
            'fallback' => true,
            'notification' => get_class($notification),
        ]);
    }
}
