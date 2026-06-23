<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Schedule::command('appointments:update-status')
    ->everyFifteenMinutes()
    ->withoutOverlapping();

Schedule::command('appointments:send-reminders')
    ->everyMinute()
    ->withoutOverlapping();

Schedule::command('queue:work --stop-when-empty')
    ->everyMinute()
    ->withoutOverlapping();

Schedule::command('notifications:archive')
    ->weekly()
    ->withoutOverlapping();

Schedule::command('vaccinations:send-reminders')
    ->daily()
    ->withoutOverlapping();

Schedule::command('external-bookings:sync-google-sheet')
    ->cron(config('services.google_sheets.sync_schedule', '*/15 * * * *'))
    ->when(fn () => (bool) config('services.google_sheets.sync_schedule_enabled'))
    ->withoutOverlapping();

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');
