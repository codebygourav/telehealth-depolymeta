<?php

namespace App\Models;

use App\Enums\VaccinationStatus;
use App\Enums\PatientVaccinationProgramStatus;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class PatientVaccinationProgram extends Model
{
    use HasFactory;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'patient_profile_id',
        'vaccination_program_id',
        'vaccination_template_id',
        'doctor_id',
        'start_date',
        'status',
    ];

    protected $casts = [
        'start_date' => 'date',
        'status' => PatientVaccinationProgramStatus::class,
    ];

    protected static function booted(): void
    {
        static::creating(function (PatientVaccinationProgram $program) {
            if (! $program->getKey()) {
                $program->{$program->getKeyName()} = (string) Str::uuid();
            }
        });

        static::saving(function (PatientVaccinationProgram $program) {
            if ($program->vaccination_template_id) {
                $templateProgramId = VaccinationTemplate::query()
                    ->where('id', $program->vaccination_template_id)
                    ->value('vaccination_program_id');

                if ($templateProgramId) {
                    $program->vaccination_program_id = $templateProgramId;
                }
            }
        });

        static::created(function (PatientVaccinationProgram $program) {
            $program->generateVaccinationRows();
        });
    }

    public function patientProfile(): BelongsTo
    {
        return $this->belongsTo(PatientProfile::class);
    }

    public function vaccinationProgram(): BelongsTo
    {
        return $this->belongsTo(VaccinationProgram::class);
    }

    public function vaccinationTemplate(): BelongsTo
    {
        return $this->belongsTo(VaccinationTemplate::class);
    }

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class);
    }

    public function patientVaccinations(): HasMany
    {
        return $this->hasMany(PatientVaccination::class);
    }

    public function generateVaccinationRows(): void
    {
        $this->loadMissing(['patientProfile.patient', 'vaccinationTemplate.items.vaccination']);

        $patient = $this->patientProfile?->patient;
        $template = $this->vaccinationTemplate;
        if (! $patient || ! $template) {
            return;
        }

        $startDate = Carbon::parse($this->start_date)->startOfDay();
        $previousDate = $startDate->copy();

        foreach ($template->items as $index => $item) {
            $baseDate = $item->depends_on_previous_dose ? $previousDate : $startDate;
            $months = (int) (($item->depends_on_previous_dose ? $item->interval_months : $item->due_after_months) ?? 0);
            $days = (int) (($item->depends_on_previous_dose ? $item->interval_days : $item->due_after_days) ?? 0);
            $scheduledDate = $baseDate->copy()->addMonths($months)->addDays($days);
            $previousDate = $scheduledDate->copy()->startOfDay();

            PatientVaccination::updateOrCreate([
                'patient_id' => $patient->id,
                'patient_profile_id' => $this->patient_profile_id,
                'doctor_id' => $this->doctor_id,
                'vaccination_id' => $item->vaccination_id,
                'vaccination_template_id' => $template->id,
                'dose_no' => $item->dose_no ?? ($index + 1),
                'set_name' => $item->set_name,
            ], [
                'patient_vaccination_program_id' => $this->id,
                'set_name' => $item->set_name,
                'set_sort_order' => $item->set_sort_order ?? 0,
                'recommended_age_label' => $item->recommended_age_label,
                'dose_no' => $item->dose_no ?? ($index + 1),
                'first_dose_date' => $startDate->toDateString(),
                'due_after_days' => $item->due_after_days ?? 0,
                'due_after_months' => $item->due_after_months ?? 0,
                'scheduled_date' => $scheduledDate->toDateString(),
                'status' => VaccinationStatus::SCHEDULED,
                'manufacturer' => $item->vaccination?->manufacturer,
                'reminder_sent' => false,
                'next_reminder_at' => $scheduledDate->copy()->subDay(),
            ]);
        }
    }
}
