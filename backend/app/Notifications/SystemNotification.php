<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use NotificationChannels\Expo\ExpoChannel;
use NotificationChannels\Expo\ExpoMessage;
use NotificationChannels\WebPush\WebPushChannel;
use NotificationChannels\WebPush\WebPushMessage;

class SystemNotification extends Notification
{

    use Queueable;

    public function __construct(
        public string $title,
        public string $message,
        public string $type, // This will be stored as event_type
        public string $category, // appointment / review / system
        public ?string $entityType = null,
        public $entityId = null,
        public array $meta = []
    ) {
        $this->meta = $this->formatDatesAndTimes($meta);
    }

    /**
     * Recursively format dates and times in the metadata to be human-readable.
     */
    protected function formatDatesAndTimes(array $data): array
    {
        $formatted = [];

        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $formatted[$key] = $this->formatDatesAndTimes($value);
            } elseif (is_string($value)) {
                $formatted[$key] = $this->formatDateOrTime($value);
            } else {
                $formatted[$key] = $value;
            }
        }

        return $formatted;
    }

    /**
     * Try to match strings against known date/time patterns and format them.
     */
    protected function formatDateOrTime(string $value): string
    {
        // 1. Time string (e.g., 14:00 or 14:00:00) -> 02:00 PM
        if (preg_match('/^([0-1]?[0-9]|2[0-3]):([0-5][0-9])(:[0-5][0-9])?$/', $value)) {
            try {
                return \Carbon\Carbon::parse($value)->format('h:i A');
            } catch (\Exception $e) {
                return $value;
            }
        }

        // 2. Standard Date string (e.g., 2026-02-26) -> 26 Feb 2026
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            try {
                return \Carbon\Carbon::parse($value)->format('d M Y');
            } catch (\Exception $e) {
                return $value;
            }
        }

        // 3. DateTime string (e.g., 2026-02-26 14:00:00) -> 26 Feb 2026, 02:00 PM
        if (preg_match('/^\d{4}-\d{2}-\d{2} ([0-1]?[0-9]|2[0-3]):[0-5][0-9]:[0-5][0-9]$/', $value)) {
            try {
                return \Carbon\Carbon::parse($value)->format('d M Y, h:i A');
            } catch (\Exception $e) {
                return $value;
            }
        }

        // 4. ISO 8601 string (e.g., 2026-02-26T14:00:00.000Z) -> 26 Feb 2026, 02:00 PM
        if (preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}(\.\d+)?Z$/', $value)) {
            try {
                return \Carbon\Carbon::parse($value)->format('d M Y, h:i A');
            } catch (\Exception $e) {
                return $value;
            }
        }

        return $value;
    }

    public function via($notifiable)
    {
        return [\App\Channels\CustomDatabaseChannel::class, ExpoChannel::class, WebPushChannel::class];
    }

    public function toExpo($notifiable)
    {
        return ExpoMessage::create()
            ->badge(1)
            ->playSound()
            ->title($this->title)
            ->body($this->message)
            ->data([
                'type' => $this->type,
                'entityType' => $this->entityType,
                'entityId' => $this->entityId,
                'meta' => $this->meta
            ]);
    }

    public function toWebPush($notifiable, $notification)
    {
        return (new WebPushMessage)
            ->title($this->title)
            ->body($this->message)
            ->data([
                'type' => $this->type,
                'entityType' => $this->entityType,
                'entityId' => $this->entityId,
                'meta' => $this->meta
            ]);
    }


    public function toArray($notifiable)
    {
        return [
            'title' => $this->title,
            'message' => $this->message,
            'type' => $this->type,
            'event_type' => $this->type,
            'entity' => [
                'type' => $this->entityType,
                'id' => $this->entityId,
            ],
            'meta' => $this->meta
        ];
    }
}
