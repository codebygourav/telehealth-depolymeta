<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('custom_cron_jobs', function (Blueprint $table) {
            $table->id();
            $table->string('command');
            $table->string('description')->nullable();
            $table->string('schedule')->default('* * * * *');
            $table->boolean('is_active')->default(true);
            $table->boolean('is_system')->default(false);
            $table->timestamp('last_run_at')->nullable();
            $table->string('last_run_status')->nullable();
            $table->text('last_run_output')->nullable();
            $table->timestamps();
        });

        // Seed default 7 system tasks
        DB::table('custom_cron_jobs')->insert([
            [
                'command' => 'appointments:update-status',
                'description' => 'Update appointments status based on duration rules',
                'schedule' => '*/15 * * * *',
                'is_active' => true,
                'is_system' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'command' => 'queue:work --stop-when-empty',
                'description' => 'Process background queued tasks',
                'schedule' => '* * * * *',
                'is_active' => true,
                'is_system' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'command' => 'notifications:archive',
                'description' => 'Archive old notification logs',
                'schedule' => '0 0 * * 0',
                'is_active' => true,
                'is_system' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'command' => 'vaccinations:send-reminders',
                'description' => 'Send daily vaccination alerts to patients',
                'schedule' => '0 8 * * *',
                'is_active' => true,
                'is_system' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'command' => 'appointments:send-reminders',
                'description' => 'Send queue status and booking notifications',
                'schedule' => '* * * * *',
                'is_active' => true,
                'is_system' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'command' => 'prescriptions:send-reminders',
                'description' => 'Send medicine intake alerts',
                'schedule' => '* * * * *',
                'is_active' => true,
                'is_system' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'command' => 'external-bookings:sync-google-sheet',
                'description' => 'Sync doctor calendars with Google Sheets',
                'schedule' => '*/15 * * * *',
                'is_active' => true,
                'is_system' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('custom_cron_jobs');
    }
};
