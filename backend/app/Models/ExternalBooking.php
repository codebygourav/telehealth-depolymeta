<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExternalBooking extends Model
{
    use HasUuids, SoftDeletes;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'doctor_id',
        'availability_id',
        'availability_override_id',
        'source',
        'import_batch_id',
        'source_row_id',
        'source_doctor_id',
        'doctor_name',
        'patient_name',
        'patient_unit_number',
        'patient_email',
        'mobile',
        'appointment_date',
        'start_time',
        'end_time',
        'consultation_type',
        'opd_type',
        'track_upload_status',
        'stack_upload_status',
        'source_created_at',
        'raw_payload',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'appointment_date' => 'date',
        'source_created_at' => 'datetime',
        'raw_payload' => 'array',
    ];

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class);
    }

    public function availability(): BelongsTo
    {
        return $this->belongsTo(DoctorAvailability::class, 'availability_id');
    }

    public function availabilityOverride(): BelongsTo
    {
        return $this->belongsTo(DoctorAvailabilityOverride::class, 'availability_override_id');
    }
}
