<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class VoiceTranscriptionLog extends Model
{
    public const STATUS_SUCCESS = 'success';
    public const STATUS_FAILED  = 'failed';

    public $incrementing = false;
    protected $keyType   = 'string';

    protected $fillable = [
        'module',
        'module_record_id',
        'appointment_id',
        'doctor_id',
        'patient_id',
        'transcript',
        'audio_duration_seconds',
        'language',
        'model',
        'audio_mime_type',
        'confidence',
        'credits_used',
        'deepgram_request_id',
        'deepgram_response',
        'status',
        'error_message',
        'created_by',
    ];

    protected $casts = [
        'deepgram_response'      => 'array',
        'audio_duration_seconds' => 'float',
        'confidence'             => 'float',
        'credits_used'           => 'float',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (self $model): void {
            if (! $model->id) {
                $model->id = (string) Str::uuid();
            }
            if (Auth::check()) {
                $model->created_by = Auth::id();
            }
        });
    }

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class);
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class);
    }

    public function getDurationFormattedAttribute(): string
    {
        $seconds = (int) $this->audio_duration_seconds;
        if ($seconds < 60) {
            return "{$seconds}s";
        }
        $m = intdiv($seconds, 60);
        $s = $seconds % 60;
        return "{$m}m {$s}s";
    }

    // Scopes
    public function scopeSuccess($query)
    {
        return $query->where('status', self::STATUS_SUCCESS);
    }

    public function scopeFailed($query)
    {
        return $query->where('status', self::STATUS_FAILED);
    }

    public function scopeThisMonth($query)
    {
        return $query->whereMonth('created_at', now()->month)
                     ->whereYear('created_at', now()->year);
    }

    public function scopeToday($query)
    {
        return $query->whereDate('created_at', now()->toDateString());
    }
}
