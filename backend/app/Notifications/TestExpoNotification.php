<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use NotificationChannels\Expo\ExpoChannel;
use NotificationChannels\Expo\ExpoMessage;

class TestExpoNotification extends Notification
{
    use Queueable;

    public function __construct(
        public string $title,
        public string $body
    ) {}

    public function via($notifiable)
    {
        return [ExpoChannel::class];
    }

    public function toExpo($notifiable)
    {
        return ExpoMessage::create()
            ->title($this->title)
            ->body($this->body)
            ->data([
                'type' => 'test',
            ]);
    }
}
