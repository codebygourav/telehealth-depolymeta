<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cron_settings', function (Blueprint $table) {
            $table->boolean('appointments_update_status_enabled')->default(true);
            $table->timestamp('appointments_update_status_last_run')->nullable();
            $table->string('appointments_update_status_status')->nullable();

            $table->boolean('notifications_archive_enabled')->default(true);
            $table->timestamp('notifications_archive_last_run')->nullable();
            $table->string('notifications_archive_status')->nullable();

            $table->boolean('external_bookings_sync_google_sheet_enabled')->default(true);
            $table->timestamp('external_bookings_sync_google_sheet_last_run')->nullable();
            $table->string('external_bookings_sync_google_sheet_status')->nullable();

            $table->timestamp('queue_last_run')->nullable();
            $table->string('queue_status')->nullable();

            $table->timestamp('vaccinations_reminder_last_run')->nullable();
            $table->string('vaccinations_reminder_status')->nullable();

            $table->timestamp('appointments_reminder_last_run')->nullable();
            $table->string('appointments_reminder_status')->nullable();

            $table->timestamp('prescriptions_reminder_last_run')->nullable();
            $table->string('prescriptions_reminder_status')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('cron_settings', function (Blueprint $table) {
            $table->dropColumn([
                'appointments_update_status_enabled',
                'appointments_update_status_last_run',
                'appointments_update_status_status',
                'notifications_archive_enabled',
                'notifications_archive_last_run',
                'notifications_archive_status',
                'external_bookings_sync_google_sheet_enabled',
                'external_bookings_sync_google_sheet_last_run',
                'external_bookings_sync_google_sheet_status',
                'queue_last_run',
                'queue_status',
                'vaccinations_reminder_last_run',
                'vaccinations_reminder_status',
                'appointments_reminder_last_run',
                'appointments_reminder_status',
                'prescriptions_reminder_last_run',
                'prescriptions_reminder_status',
            ]);
        });
    }
};
