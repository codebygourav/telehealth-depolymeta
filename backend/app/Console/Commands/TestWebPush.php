<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Notifications\SystemNotification;


class TestWebPush extends Command
{
    protected $signature = 'notification:test-webpush {email}';

    protected $description = 'Send a test Web Push notification to a user by email';

    public function handle()
    {
        $email = $this->argument('email');
        $user = User::where('email', $email)->first();

        if (!$user) {
            $this->error("User with email '{$email}' not found.");
            return Command::FAILURE;
        }

        $this->info("Sending test Web Push notification to {$user->name}...");

        $user->notify(new SystemNotification(
            title: 'Test Web Push Notification',
            message: 'Hello! This is a test Web Push notification sent at ' . now()->toTimeString(),
            type: 'system_test',
            category: 'system',
            entityType: null,
            entityId: null,
            meta: [
                'test' => true,
                'click_action' => '/admin'
            ]
        ));

        $this->info("Notification dispatched successfully.");
        return Command::SUCCESS;
    }
}
