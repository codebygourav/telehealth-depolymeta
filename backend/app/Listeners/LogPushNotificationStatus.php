<?php

namespace App\Listeners;

use Illuminate\Notifications\Events\NotificationSent;
use Illuminate\Notifications\Events\NotificationFailed;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Events\Dispatcher;

class LogPushNotificationStatus
{
    /**
     * Handle successfully sent notifications
     */
    public function handleSent(NotificationSent $event): void
    {
        $this->updateStatus($event, 'success', true);
    }

    /**
     * Handle failed notifications
     */
    public function handleFailed(NotificationFailed $event): void
    {
        $status = 'failed';
        
        // Sometimes the Expo error might indicate device_not_registered if it's an invalid token
        if (isset($event->data['error']) || isset($event->data['message'])) {
            $error = $event->data['error'] ?? $event->data['message'] ?? '';
            if (is_string($error) && str_contains(strtolower($error), 'notregistered')) {
                $status = 'device_not_registered';
            }
        }
        
        $this->updateStatus($event, $status, false);
    }

    /**
     * Register the listeners for the subscriber.
     *
     * @param  \Illuminate\Events\Dispatcher  $events
     * @return void
     */
    public function subscribe(Dispatcher $events): void
    {
        $events->listen(
            NotificationSent::class,
            [LogPushNotificationStatus::class, 'handleSent']
        );

        $events->listen(
            NotificationFailed::class,
            [LogPushNotificationStatus::class, 'handleFailed']
        );
    }

    /**
     * Update the database notification record.
     */
    private function updateStatus($event, string $status, bool $isSent): void
    {
        if ($event->channel === \NotificationChannels\Expo\ExpoChannel::class) {
            
            // Safety check to ensure we have an ID for the database
            if (!isset($event->notification->id)) {
                return;
            }

            try {
                DB::table('notifications')
                    ->where('id', $event->notification->id)
                    ->update([
                        'is_push_sent' => $isSent,
                        'push_sent_at' => now(),
                        'push_status' => $status,
                    ]);
            } catch (\Exception $e) {
                Log::error("Failed to update push status for notification {$event->notification->id}: " . $e->getMessage());
            }
        }
    }
}
