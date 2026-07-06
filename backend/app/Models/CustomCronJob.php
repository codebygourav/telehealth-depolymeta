<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CustomCronJob extends Model
{
    protected $table = 'custom_cron_jobs';

    protected $fillable = [
        'command',
        'description',
        'schedule',
        'is_active',
        'is_system',
        'last_run_at',
        'last_run_status',
        'last_run_output',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_system' => 'boolean',
        'last_run_at' => 'datetime',
    ];
}
