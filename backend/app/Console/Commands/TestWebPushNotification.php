<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Notifications\SystemNotification;
use Illuminate\Console\Command;

class TestWebPushNotification extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'notification:test-webpush {email : The email of the user to notify}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Trigger a test WebPush notification to a specific user by email';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $email = $this->argument('email');
        $user = User::where('email', $email)->first();

        if (!$user) {
            $this->error("User not found with email: {$email}");
            return Command::FAILURE;
        }

        $this->info("Sending test WebPush notification to user: {$user->name} ({$user->id})");

        // Verify if user has any subscriptions
        $subscriptionsCount = $user->pushSubscriptions()->count();
        $this->info("User has {$subscriptionsCount} active push subscription(s).");

        if ($subscriptionsCount === 0) {
            $this->warn("User has 0 push subscriptions registered. The WebPush channel will skip dispatching unless a browser subscription is registered.");
        }

        $notification = new SystemNotification(
            title: 'WebPush Test Alert',
            message: 'Hello ' . $user->name . '! This is a test WebPush notification from Deploymeta.',
            type: 'webpush_test',
            category: 'system',
            meta: [
                'triggered_at' => now()->toIso8601String(),
                'test_mode' => true
            ]
        );

        try {
            $user->notify($notification);
            $this->info("Notification dispatched successfully!");
        } catch (\Throwable $e) {
            $this->error("Failed to send notification: " . $e->getMessage());
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}
