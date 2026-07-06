<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CronSetting extends Model
{
    protected $table = 'cron_settings';

    protected $fillable = [
        'is_enabled',
        'memory_limit',
        'queue_enabled',
        'appointments_reminder_enabled',
        'prescriptions_reminder_enabled',
        'vaccinations_reminder_enabled',
        'last_run_at',
        'last_run_status',
        'last_run_output',

        // New fields
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
    ];

    protected $casts = [
        'is_enabled' => 'boolean',
        'queue_enabled' => 'boolean',
        'appointments_reminder_enabled' => 'boolean',
        'prescriptions_reminder_enabled' => 'boolean',
        'vaccinations_reminder_enabled' => 'boolean',
        'last_run_at' => 'datetime',

        'appointments_update_status_enabled' => 'boolean',
        'appointments_update_status_last_run' => 'datetime',
        'notifications_archive_enabled' => 'boolean',
        'notifications_archive_last_run' => 'datetime',
        'external_bookings_sync_google_sheet_enabled' => 'boolean',
        'external_bookings_sync_google_sheet_last_run' => 'datetime',

        'queue_last_run' => 'datetime',
        'vaccinations_reminder_last_run' => 'datetime',
        'appointments_reminder_last_run' => 'datetime',
        'prescriptions_reminder_last_run' => 'datetime',
    ];
}
