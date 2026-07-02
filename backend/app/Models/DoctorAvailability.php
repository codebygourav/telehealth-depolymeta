<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Enums\DayOfWeek;
use App\Services\DoctorAvailabilityValidationService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use App\Enums\AppointmentStatus;

class DoctorAvailability extends Model
{
    use HasFactory, HasUuids, SoftDeletes;
    protected $table = 'availabilities';
    protected $primaryKey = 'id';
    protected $keyType = 'string';
    public $incrementing = false;


    protected $fillable = [
        'doctor_id',
        'date',
        'day_of_week',
        'start_time',
        'end_time',
        'capacity',
        'consultation_type',
        'is_available',
        'is_recurring',
        'recurring_start_date',
        'recurring_end_date',
        'opd_type',
        'consultation_fee',
        'is_child_only',
        'recurring_months',
        'doctor_room',
        'created_by',
        'updated_by',
        'deleted_by',
        'blocked_dates',
        'booking_cutoff_rules',
        'is_auto_recurring',
        // 'series_id',
        // 'custom_dates',
    ];

    protected $casts = [
        'start_time' => 'datetime:H:i',
        'end_time' => 'datetime:H:i',
        'is_available' => 'boolean',
        'is_recurring' => 'boolean',
        'recurring_start_date' => 'date',
        'recurring_end_date' => 'date',
        'opd_type' => 'string',
        'consultation_fee' => 'decimal:2',
        'is_child_only' => 'boolean',
        'recurring_months' => 'integer',
        'blocked_dates' => 'array',
        'booking_cutoff_rules' => 'array',
        'is_auto_recurring' => 'boolean',
        // 'custom_dates' => 'array',
    ];

    protected $attributes = [
        'consultation_type' => 'in-person',
        'is_available' => true,
        'capacity' => 1,
        'is_recurring' => false,
        'is_child_only' => false,
        'is_auto_recurring' => false,
    ];
    /**
     * Get the doctor that owns the availability.
     */
    public function doctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class);
    }

    /**
     * Get the appointments for the availability.
     */
    public function appointments()
    {
        return $this->hasMany(Appointment::class, 'availability_id');
    }


    
    public function overrides()
    {
        return $this->hasMany(DoctorAvailabilityOverride::class, 'doctor_availability_id');
    }

    public function hasBookedAppointments(): bool
    {
        return $this->appointments()
            ->whereNotIn('status', [
                AppointmentStatus::CANCELLED->value,
                AppointmentStatus::FAILED->value,
            ])
            ->exists();
    }

    public function getFormattedDateAttribute(): ?string
    {
        if (empty($this->date)) {
            return null;
        }

        // Safely check for null or invalid date
        try {
            $carbonDate = $this->date instanceof \Carbon\Carbon
                ? $this->date
                : (!empty($this->date) ? \Carbon\Carbon::parse($this->date) : null);

            if ($carbonDate === null) {
                return null;
            }

            return $carbonDate->format('d-m-Y');
        } catch (\Throwable $e) {
            return null;
        }
    }


    /**
     * Get the day of week options.
     */
    public static function getDayOptions(): array
    {
        return DayOfWeek::ordered();
    }


    /**
     * Get the consultation type options.
     */
    public static function getConsultationTypeOptions(): array
    {
        return [
            'in-person' => 'In-Person',
            'video' => 'Video',
        ];
    }

    protected static function boot()
    {
        parent::boot();
        static::saving(function ($model) {
            $validationService = app(DoctorAvailabilityValidationService::class);

            // Normalize times - handle both string and Carbon instances
            $startTime = $model->start_time instanceof Carbon
                ? $model->start_time->format('H:i')
                : ($model->start_time ? $validationService->normalizeTime($model->start_time) : null);
            $endTime = $model->end_time instanceof Carbon
                ? $model->end_time->format('H:i')
                : ($model->end_time ? $validationService->normalizeTime($model->end_time) : null);

            // 1. Validate time range (start < end)
            if ($startTime && $endTime) {
                $timeErrors = $validationService->validateTimeRange($startTime, $endTime);
                if (!empty($timeErrors)) {
                    throw ValidationException::withMessages([
                        'end_time' => $timeErrors[0],
                    ]);
                }
            }

            // 2. Check for duplicates and overlaps in database
            if ($model->doctor_id && $startTime && $endTime) {
                $isRecurring = (bool)$model->is_recurring;
                $date = $isRecurring ? null : ($model->date instanceof Carbon ? $model->date->format('Y-m-d') : ($model->date ? $validationService->normalizeDate($model->date) : null));

                // For validation, if recurring, we need the "intended" day
                $dayOfWeek = $model->day_of_week;
                if ($isRecurring && !$dayOfWeek && $model->recurring_start_date) {
                    $dayOfWeek = Carbon::parse($model->recurring_start_date)->format('l');
                }

                $excludeId = $model->exists ? $model->id : null;

                // Check for exact duplicate
                if ($validationService->slotExistsInDatabase(
                    $model->doctor_id,
                    $date,
                    $startTime,
                    $endTime,
                    $model->consultation_type ?? 'in-person',
                    $excludeId,
                    $dayOfWeek,
                    $model->recurring_start_date?->format('Y-m-d') ?? $model->recurring_start_date,
                    $model->recurring_end_date?->format('Y-m-d') ?? $model->recurring_end_date
                )) {
                    $dateStr = $date
                        ? Carbon::parse($date)->format('M d, Y')
                        : "this recurring pattern on " . ucfirst($dayOfWeek);
                    throw ValidationException::withMessages([
                        'start_time' => "This doctor already has an availability slot from {$startTime} to {$endTime} on {$dateStr}. Duplicate availability is not allowed in telehealth scheduling.",
                    ]);
                }

                // Check for overlaps
                $overlaps = $validationService->slotOverlapsInDatabase(
                    $model->doctor_id,
                    $date,
                    $startTime,
                    $endTime,
                    $model->consultation_type ?? 'in-person',
                    $excludeId,
                    $dayOfWeek,
                    $model->recurring_start_date?->format('Y-m-d') ?? $model->recurring_start_date,
                    $model->recurring_end_date?->format('Y-m-d') ?? $model->recurring_end_date
                );
                if (!empty($overlaps)) {
                    $overlap = $overlaps[0];
                    $dateStr = $date
                        ? Carbon::parse($date)->format('M d, Y')
                        : "this recurring pattern on " . ucfirst($dayOfWeek);
                    throw ValidationException::withMessages([
                        'start_time' => "This time overlaps with another availability ({$overlap['start_time']}–{$overlap['end_time']} on {$dateStr}). A doctor cannot be available in two places at the same time.",
                    ]);
                }
            }

            // 3. Clean up fields based on recurring status
            if ($model->is_recurring) {
                $model->date = null;
                // $model->day_of_week = null; // ✅ THIS WAS WIPING THE DAY! REMOVED.
            }

            // 3. Set recurring dates if needed
            if ($model->is_recurring && !$model->recurring_start_date && !$model->recurring_end_date) {
                $start = Carbon::now();
                $months = $model->recurring_months ?? 3;
                $end = $start->copy()->addMonths($months);

                $model->recurring_start_date = $start;
                $model->recurring_end_date = $end;
            }
        });

        static::creating(function ($model) {
            if (Auth::check()) {
                $model->created_by = Auth::id();
            }
        });

        static::updating(function ($model) {
            if (Auth::check()) {
                $model->updated_by = Auth::id();
            }
        });

        static::deleting(function ($model) {
            if (Auth::check()) {
                $model->deleted_by = Auth::id();
            }
        });
    }
    /**
     * Get formatted time range.
     */
    public function getTimeRangeAttribute(): ?string
    {
        try {
            if (!$this->start_time || !$this->end_time) {
                return null;
            }
            return $this->start_time->format('g:i A') . ' - ' . $this->end_time->format('g:i A');
        } catch (\Throwable $e) {
            return null;
        }
    }


    public function getDayNameAttribute(): string
    {
        return ucfirst($this->day_of_week);
    }

    /**
     * Get consultation type label.
     */
    public function getConsultationTypeLabelAttribute(): string
    {
        return self::getConsultationTypeOptions()[$this->consultation_type] ?? $this->consultation_type;
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by', 'id');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by', 'id');
    }

    public function deleter()
    {
        return $this->belongsTo(User::class, 'deleted_by', 'id');
    }

    /**
     * Get effective doctor considering active replacements
     */
    public function getEffectiveDoctorAttribute()
    {
        $replacementService = app(\App\Services\DoctorReplacementService::class);

        if ($replacementService->hasActiveReplacements($this->doctor_id, $this->date)) {
            $replacements = $replacementService->getActiveReplacements($this->doctor_id, $this->date);
            foreach ($replacements as $replacement) {
                if ($replacement->getEffectiveDoctorForDate($this->date)) {
                    return $replacement->replacementDoctor;
                }
            }
        }

        return $this->doctor;
    }

    /**
     * Check if this availability slot has been transferred due to replacement
     */
    public function isTransferredDueToReplacement(): bool
    {
        return \App\Models\DoctorReplacement::where('replacement_doctor_id', $this->doctor_id)
            ->where('is_active', true)
            ->where('created_at', '<=', $this->created_at)
            ->when($this->date, function ($query) {
                $query->where(function ($q) {
                    $q->whereNull('start_date')
                        ->orWhere('start_date', '<=', $this->date);
                })->where(function ($q) {
                    $q->whereNull('end_date')
                        ->orWhere('end_date', '>=', $this->date);
                });
            })
            ->exists();
    }

    /**
     * Get the replacement that caused this availability transfer
     */
    public function getTransferringReplacement()
    {
        return \App\Models\DoctorReplacement::where('replacement_doctor_id', $this->doctor_id)
            ->where('is_active', true)
            ->where('created_at', '<=', $this->created_at)
            ->when($this->date, function ($query) {
                $query->where(function ($q) {
                    $q->whereNull('start_date')
                        ->orWhere('start_date', '<=', $this->date);
                })->where(function ($q) {
                    $q->whereNull('end_date')
                        ->orWhere('end_date', '>=', $this->date);
                });
            })
            ->first();
    }

    /**
     * Scope a query to only include available slots in a date range.
     */
    public function scopeAvailableInRange($query, $startDate, $endDate)
    {
        return $query->where('is_available', true)
            ->withoutTestDoctors()
            ->whereNull('deleted_at')
            ->where(function ($subQ) use ($startDate, $endDate) {
                $subQ->where(function ($q) use ($startDate, $endDate) {
                    $q->where('is_recurring', false)
                        ->where('date', '>=', $startDate)
                        ->where('date', '<=', $endDate);
                })
                    ->orWhere(function ($q) use ($startDate, $endDate) {
                        $q->where('is_recurring', true)
                            ->where(function ($query) use ($endDate) {
                                $query->whereNull('recurring_start_date')
                                    ->orWhere('recurring_start_date', '<=', $endDate);
                            })
                            ->where(function ($query) use ($startDate) {
                                $query->whereNull('recurring_end_date')
                                    ->orWhere('recurring_end_date', '>=', $startDate);
                            });
                    })
                    ->orWhere(function ($q) {
                        $q->where('is_recurring', false)
                            ->whereNull('date')
                            ->whereNotNull('day_of_week');
                    });
            });
    }

    public function scopeWithoutTestDoctors($query)
    {
        return $query->whereHas('doctor', function ($query) {
            $query->withoutTestDoctors();
        });
    }

    public function isBlockedOnDate($date): bool
    {
        return app(\App\Services\DoctorAvailabilityService::class)->isDateBlocked($this, $date);
    }
}