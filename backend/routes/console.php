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

Schedule::command('notifications:archive')
    ->weekly()
    ->withoutOverlapping();

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');
