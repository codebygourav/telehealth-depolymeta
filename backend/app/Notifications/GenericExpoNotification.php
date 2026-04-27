<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;
use NotificationChannels\Expo\ExpoChannel;
use NotificationChannels\Expo\ExpoMessage;

class GenericExpoNotification extends Notification
{

    public $title;
    public $body;
    public $data;

    /**
     * Create a new notification instance.
     *
     * @param string $title
     * @param string $body
     * @param array $data
     */
    public function __construct($title, $body, array $data = [])
    {
        $this->title = $title;
        $this->body = $body;
        $this->data = $data;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database', ExpoChannel::class];
    }

    /**
     * Get the Expo representation of the notification.
     */
    public function toExpo($notifiable)
    {
        return ExpoMessage::create()
            ->title($this->title)
            ->body($this->body)
            ->data($this->data);
    }

    /**
     * Get the array representation of the notification for the database.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return array_merge([
            'title' => $this->title,
            'body' => $this->body,
        ], $this->data);
    }
}
