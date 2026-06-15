<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class PaidAppointment extends Model
{
    use HasUuids;

    protected $connection = 'paid_appointments';

    protected $table = 'paid_appointment';

    protected $fillable = [
        'doctor_id',
        'source_row_id',
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
        'payment_id',
    ];

    protected $casts = [
        'appointment_date' => 'date',
        'source_created_at' => 'datetime',
    ];
}
