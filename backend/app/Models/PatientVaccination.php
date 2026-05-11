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
        'doctor_id',
        'vaccination_id',
        'vaccination_template_id',
        'set_name',
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
        'given_at',
        'given_by',
        'doctor_notes',
        'side_effect_observed',
        'patient_reaction',
        'reminder_sent',
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

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(VaccinationDocument::class);
    }
}
