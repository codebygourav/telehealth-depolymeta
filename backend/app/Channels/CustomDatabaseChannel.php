<?php

namespace App\Channels;

use Illuminate\Notifications\Notification;
use App\Notifications\SystemNotification;

class CustomDatabaseChannel
{
    /**
     * Send the given notification.
     *
     * @param mixed $notifiable
     * @param \Illuminate\Notifications\Notification $notification
     * @return \Illuminate\Database\Eloquent\Model
     */
    public function send($notifiable, Notification $notification)
    {
        $data = method_exists($notification, 'toArray') 
            ? $notification->toArray($notifiable) 
            : [];
        
        // Populate extra columns if it's our SystemNotification
        $category = ($notification instanceof SystemNotification) ? $notification->category : 'system';
        $eventType = ($notification instanceof SystemNotification) ? $notification->type : get_class($notification);
        $entityId = ($notification instanceof SystemNotification) ? $notification->entityId : null;
        $entityType = ($notification instanceof SystemNotification) ? $notification->entityType : null;

        $record = $notifiable->routeNotificationFor('database', $notification)->create([
            'id' => $notification->id,
            'type' => get_class($notification),
            'category' => $category,
            'event_type' => $eventType,
            'entity_id' => $entityId,
            'entity_type' => $entityType,
            'data' => $data,
            'read_at' => null,
        ]);



        \Illuminate\Support\Facades\Log::info("CustomDatabaseChannel: Saving notification to DB", [
            'notifiable_id' => $notifiable->id,
            'event_type' => $eventType,
            'category' => $category
        ]);

        return $record;
    }
}
