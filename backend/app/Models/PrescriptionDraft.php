<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class PrescriptionDraft extends Model
{
    public const STATUS_PARSED = 'parsed';
    public const STATUS_APPLIED = 'applied';
    public const STATUS_REJECTED = 'rejected';

    public const SOURCE_TEXT = 'text';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'appointment_id',
        'doctor_id',
        'patient_id',
        'source_type',
        'status',
        'input_text',
        'parsed_payload',
        'warnings',
        'missing_fields',
        'confidence_score',
        'submitted_payload',
        'applied_at',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'parsed_payload' => 'array',
        'warnings' => 'array',
        'missing_fields' => 'array',
        'submitted_payload' => 'array',
        'confidence_score' => 'integer',
        'applied_at' => 'datetime',
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

        static::updating(function (self $model): void {
            if (Auth::check()) {
                $model->updated_by = Auth::id();
            }
        });
    }

    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class);
    }

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class);
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }
}
