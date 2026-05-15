<?php

namespace App\Models;

use App\Enums\VaccinationStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class PatientVaccination extends Model
{
    use HasFactory, SoftDeletes;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'patient_id',
        'patient_profile_id',
        'doctor_id',
        'vaccination_id',
        'vaccination_template_id',
        'patient_vaccination_program_id',
        'set_name',
        'set_description',
        'set_sort_order',
        'recommended_age_label',
        'dose_no',
        'first_dose_date',
        'due_after_days',
        'due_after_months',
        'scheduled_date',
        'completed_date',
        'status',
        'batch_number',
        'manufacturer',
        'route',
        'site',
        'dose_amount',
        'given_at',
        'given_by',
        'doctor_notes',
        'side_effect_observed',
        'patient_reaction',
        'reminder_sent',
        'last_reminder_sent_at',
        'reminder_count',
        'next_reminder_at',
    ];

    protected $casts = [
        'first_dose_date' => 'date',
        'scheduled_date' => 'date',
        'completed_date' => 'date',
        'set_sort_order' => 'integer',
        'due_after_days' => 'integer',
        'due_after_months' => 'integer',
        'status' => VaccinationStatus::class,
        'reminder_sent' => 'boolean',
        'last_reminder_sent_at' => 'datetime',
        'reminder_count' => 'integer',
        'next_reminder_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (PatientVaccination $vaccination) {
            if (! $vaccination->getKey()) {
                $vaccination->{$vaccination->getKeyName()} = (string) Str::uuid();
            }
        });

        static::updating(function (PatientVaccination $vaccination) {
            if (
                $vaccination->isDirty('scheduled_date')
                && ($vaccination->status instanceof VaccinationStatus ? $vaccination->status->value : $vaccination->status) !== VaccinationStatus::COMPLETED->value
            ) {
                $vaccination->reminder_sent = false;
                $vaccination->next_reminder_at = null;
            }
        });
    }

    public function vaccination(): BelongsTo
    {
        return $this->belongsTo(Vaccination::class);
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(VaccinationTemplate::class, 'vaccination_template_id');
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function patientProfile(): BelongsTo
    {
        return $this->belongsTo(PatientProfile::class);
    }

    public function patientVaccinationProgram(): BelongsTo
    {
        return $this->belongsTo(PatientVaccinationProgram::class);
    }

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(VaccinationDocument::class);
    }
}
