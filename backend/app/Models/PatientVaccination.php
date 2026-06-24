<?php

namespace App\Models;

use App\Enums\VaccinationStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;
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
        'expected_date',
        'assigned_date',
        'due_date',
        'changed_date',
        'missed_date',
        'overdue_date',
        'grace_period_before_days',
        'grace_period_after_days',
        'skipped_reason',
        'on_hold_reason',
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
        'expected_date' => 'date',
        'assigned_date' => 'date',
        'due_date' => 'date',
        'changed_date' => 'date',
        'missed_date' => 'date',
        'overdue_date' => 'date',
        'grace_period_before_days' => 'integer',
        'grace_period_after_days' => 'integer',
    ];

    protected static function booted(): void
    {
        $originalAttributesCache = [];

        static::creating(function (PatientVaccination $vaccination) {
            if (! $vaccination->getKey()) {
                $vaccination->{$vaccination->getKeyName()} = (string) Str::uuid();
            }
        });

        static::created(function (PatientVaccination $vaccination) {
            PatientVaccinationLog::create([
                'patient_vaccination_id' => $vaccination->id,
                'performed_by_id' => Auth::user()?->id,
                'action' => 'created',
                'new_value' => 'Vaccination dose created in system',
            ]);

            // Notify patient when a vaccination is assigned
            if ($vaccination->patient && $vaccination->patient->user) {
                try {
                    \App\Services\NotificationService::notifyVaccinationAssigned($vaccination);
                } catch (\Exception $e) {
                    \Illuminate\Support\Facades\Log::error("Failed to notify vaccination assignment: " . $e->getMessage());
                }
            }
        });

        static::saving(function (PatientVaccination $vaccination) {
            // Update scheduled_date to match due_date for compatibility with old code
            if ($vaccination->due_date) {
                $vaccination->scheduled_date = $vaccination->due_date;
            }

            // Calculate missed_date and overdue_date if not set
            if ($vaccination->due_date) {
                $template = $vaccination->template;
                $overdueAlertDays = $template ? ($template->overdue_alert_days_after ?? 1) : 1;
                $vaccination->overdue_date = $vaccination->due_date->copy()->addDays($overdueAlertDays);
                $vaccination->missed_date = $vaccination->due_date->copy()->addDays($vaccination->grace_period_after_days ?: 0);
            }

            // Automatically resolve active status
            if (in_array($vaccination->status, [
                VaccinationStatus::UPCOMING,
                VaccinationStatus::DUE_SOON,
                VaccinationStatus::DUE_TODAY,
                VaccinationStatus::OVERDUE,
                VaccinationStatus::MISSED,
                VaccinationStatus::PENDING,
                VaccinationStatus::SCHEDULED,
            ], true) || empty($vaccination->status)) {
                $vaccination->status = $vaccination->resolveStatusFromDates();
            }

            $statusValue = $vaccination->status instanceof VaccinationStatus
                ? $vaccination->status->value
                : (string) $vaccination->status;

            if ($statusValue !== VaccinationStatus::COMPLETED->value) {
                $vaccination->completed_date = null;
            } elseif (! $vaccination->completed_date) {
                $vaccination->completed_date = now()->startOfDay();
            }

            if ($statusValue !== VaccinationStatus::MISSED->value) {
                $vaccination->missed_date = null;
            }

            if (! in_array($statusValue, [VaccinationStatus::OVERDUE->value, VaccinationStatus::MISSED->value], true)) {
                $vaccination->overdue_date = null;
            }

            if ($statusValue !== VaccinationStatus::SKIPPED_BY_DOCTOR->value) {
                $vaccination->skipped_reason = null;
            }

            if ($statusValue !== VaccinationStatus::ON_HOLD->value) {
                $vaccination->on_hold_reason = null;
            }
        });

        static::updating(function (PatientVaccination $vaccination) use (&$originalAttributesCache) {
            $originalAttributesCache[$vaccination->id] = $vaccination->getOriginal();

            if (
                $vaccination->isDirty('scheduled_date')
                && ($vaccination->status instanceof VaccinationStatus ? $vaccination->status->value : $vaccination->status) !== VaccinationStatus::COMPLETED->value
            ) {
                $vaccination->reminder_sent = false;
                $vaccination->next_reminder_at = null;
            }
        });

        static::updated(function (PatientVaccination $vaccination) use (&$originalAttributesCache) {
            $original = $originalAttributesCache[$vaccination->id] ?? [];
            unset($originalAttributesCache[$vaccination->id]);

            // Trigger cascading calculations for dependent doses
            if ($vaccination->wasChanged('status') && $vaccination->status === VaccinationStatus::COMPLETED) {
                $vaccination->cascadeDependentDoses();
            }

            if ($vaccination->wasChanged('status')) {
                if ($vaccination->status === VaccinationStatus::COMPLETED) {
                    \App\Services\NotificationService::notifyVaccinationCompleted($vaccination);
                } elseif ($vaccination->status === VaccinationStatus::MISSED) {
                    \App\Services\NotificationService::notifyVaccinationMissed($vaccination);
                } elseif ($vaccination->status === VaccinationStatus::OVERDUE) {
                    \App\Services\NotificationService::notifyVaccinationOverdue($vaccination);
                }
            }

            $changed = $vaccination->getChanges();
            $user = Auth::user();
            foreach ($changed as $field => $newValue) {
                if (in_array($field, ['updated_at', 'created_at', 'reminder_sent', 'last_reminder_sent_at', 'reminder_count', 'next_reminder_at'])) {
                    continue;
                }

                $oldValue = $original[$field] ?? null;

                if ($oldValue instanceof \Carbon\Carbon) {
                    $oldValue = $oldValue->toDateString();
                }
                if ($newValue instanceof \Carbon\Carbon) {
                    $newValue = $newValue->toDateString();
                }

                $reason = null;
                if ($field === 'status' && $newValue === VaccinationStatus::SKIPPED_BY_DOCTOR->value) {
                    $reason = $vaccination->skipped_reason;
                } elseif ($field === 'status' && $newValue === VaccinationStatus::ON_HOLD->value) {
                    $reason = $vaccination->on_hold_reason;
                } elseif ($field === 'due_date' || $field === 'scheduled_date') {
                    $reason = $vaccination->doctor_notes ?: 'Schedule adjusted';
                }

                PatientVaccinationLog::create([
                    'patient_vaccination_id' => $vaccination->id,
                    'performed_by_id' => $user?->id,
                    'action' => "updated_{$field}",
                    'old_value' => is_scalar($oldValue) ? (string) $oldValue : json_encode($oldValue),
                    'new_value' => is_scalar($newValue) ? (string) $newValue : json_encode($newValue),
                    'reason' => $reason,
                ]);
            }
        });
    }

    public function resolveStatusFromDates(): VaccinationStatus
    {
        if (in_array($this->status, [
            VaccinationStatus::COMPLETED,
            VaccinationStatus::CANCELLED,
            VaccinationStatus::SKIPPED_BY_DOCTOR,
            VaccinationStatus::ON_HOLD,
            VaccinationStatus::PENDING_APPROVAL,
        ], true)) {
            return $this->status;
        }

        $today = now()->startOfDay();
        $dueDate = $this->due_date ? \Carbon\Carbon::parse($this->due_date)->startOfDay() : null;
        $missedDate = $this->missed_date ? \Carbon\Carbon::parse($this->missed_date)->startOfDay() : null;

        if (!$dueDate) {
            return VaccinationStatus::UPCOMING;
        }

        if ($missedDate && $today->gt($missedDate)) {
            return VaccinationStatus::MISSED;
        }

        if ($today->gt($dueDate)) {
            return VaccinationStatus::OVERDUE;
        }

        if ($today->equalTo($dueDate)) {
            return VaccinationStatus::DUE_TODAY;
        }

        if ($today->diffInDays($dueDate) <= 3) {
            return VaccinationStatus::DUE_SOON;
        }

        return VaccinationStatus::UPCOMING;
    }

    public function cascadeDependentDoses(): void
    {
        return;
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

    public function logs(): HasMany
    {
        return $this->hasMany(PatientVaccinationLog::class)->orderBy('created_at', 'desc');
    }

    private static function addValueUnitToDate(\Carbon\Carbon $date, int $value, string $unit): \Carbon\Carbon
    {
        return match ($unit) {
            'weeks' => $date->copy()->addWeeks($value),
            'months' => $date->copy()->addMonths($value),
            'years' => $date->copy()->addYears($value),
            default => $date->copy()->addDays($value),
        };
    }
}
