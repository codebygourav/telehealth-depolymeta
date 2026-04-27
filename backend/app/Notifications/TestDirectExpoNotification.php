<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;   // 👈 ADD THIS
use Illuminate\Notifications\Notification;
use NotificationChannels\Expo\ExpoChannel;
use NotificationChannels\Expo\ExpoMessage;


class TestDirectExpoNotification extends Notification
{
    use Queueable;   // 👈 ADD THIS

    public $title;
    public $body;

    public function __construct($title, $body)
    {
        $this->title = $title;
        $this->body = $body;
    }

    public function via($notifiable)
    {
        return [ExpoChannel::class];
    }

    public function toExpo($notifiable)
    {
        return ExpoMessage::create()
            ->title($this->title)
            ->body($this->body);
    }
}
