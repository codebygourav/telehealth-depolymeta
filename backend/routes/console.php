<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

// Helper to log task execution status
if (!function_exists('updateCustomJobStatus')) {
    function updateCustomJobStatus(int $id, string $status, ?string $output = null) {
        try {
            $update = [
                'last_run_at' => now(),
                'last_run_status' => $status,
            ];
            if ($output !== null) {
                $update['last_run_output'] = $output;
            }
            \Illuminate\Support\Facades\DB::table('custom_cron_jobs')
                ->where('id', $id)
                ->update($update);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error("Failed to update custom job status for ID {$id}: " . $e->getMessage());
        }
    }
}

$settings = null;
try {
    if (\Illuminate\Support\Facades\Schema::hasTable('cron_settings')) {
        $settings = \Illuminate\Support\Facades\DB::table('cron_settings')->first();
    }
} catch (\Throwable $e) {}

// Apply scheduling if not disabled globally
if (data_get($settings, 'is_enabled', true)) {

    // Load active cron jobs from database dynamically!
    try {
        if (\Illuminate\Support\Facades\Schema::hasTable('custom_cron_jobs')) {
            $jobs = \Illuminate\Support\Facades\DB::table('custom_cron_jobs')
                ->where('is_active', true)
                ->get();

            foreach ($jobs as $job) {
                Schedule::command($job->command)
                    ->cron($job->schedule)
                    ->withoutOverlapping()
                    ->before(fn() => updateCustomJobStatus($job->id, 'Running'))
                    ->onSuccess(fn() => updateCustomJobStatus($job->id, 'Success', 'Executed successfully.'))
                    ->onFailure(fn() => updateCustomJobStatus($job->id, 'Failed', 'Execution failed. Check application logs.'));
            }
        }
    } catch (\Throwable $e) {}

    // Heartbeat callback to log VPS cron execution
    Schedule::call(function () {
        try {
            \Illuminate\Support\Facades\Log::info("Heartbeat run starting...");
            if (\Illuminate\Support\Facades\Schema::hasTable('cron_settings')) {
                $updated = \Illuminate\Support\Facades\DB::table('cron_settings')
                    ->where('id', 1)
                    ->update([
                        'last_run_at' => now(),
                        'last_run_status' => 'Success',
                        'last_run_output' => 'Scheduler executed successfully at ' . now()->toDateTimeString(),
                    ]);
                \Illuminate\Support\Facades\Log::info("Heartbeat run completed: " . ($updated ? 'updated' : 'failed'));
            }
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error("Heartbeat run error: " . $e->getMessage());
        }
    })->everyMinute();
}

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');
