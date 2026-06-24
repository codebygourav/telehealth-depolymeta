<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // Process the queue automatically every minute in shared hosting environments
        $schedule->command('queue:work --stop-when-empty')
            ->everyMinute()
            ->withoutOverlapping();

        // Send daily vaccination reminders at 8 AM
        $schedule->command('vaccinations:send-reminders')
            ->dailyAt('08:00')
            ->withoutOverlapping();

        // Send pre-queue/appointment reminders every minute
        $schedule->command('appointments:send-reminders')
            ->everyMinute()
            ->withoutOverlapping();

        // Send medicine intake reminders every minute
        $schedule->command('prescriptions:send-reminders')
            ->everyMinute()
            ->withoutOverlapping();
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__ . '/Commands');

        require base_path('routes/console.php');
    }
}
