<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use App\Enums\AppointmentStatus;
use App\Traits\InteractsWithModuleDocuments;

class Appointment extends Model
{
    use SoftDeletes, InteractsWithModuleDocuments;

    protected $moduleDocumentKeys = ['prescription_pdf'];

    protected $fillable = [
        'patient_id',
        'doctor_id',
        'replaced_by_id',
        'availability_id',
        'availability_override_id',
        'appointment_date',
        'appointment_time',
        'appointment_end_time',
        'status',
        'consultation_type',
        'visit_reason',
        'instructions_by_doctor',
        'next_visit_date',
        'stamp_preference',
        'slug',
        'fee_amount',
        'booking_source',
        'admin_payment_type',
        'payment_waived_by',
        'payment_waived_at',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    public $incrementing = false;
    protected $keyType = 'string';

    protected $casts = [
        'appointment_date' => 'date',
        'appointment_time' => 'string',
        'appointment_end_time' => 'string',
        'consultation_type' => 'string',
        'instructions_by_doctor' => 'array',
        'next_visit_date' => 'date',
        'payment_waived_at' => 'datetime',
        'visit_reason' => 'array',
        'status' => AppointmentStatus::class,
    ];

    /**
     * Backward compatibility: map legacy notes attribute to visit_reason.
     */
    public function getNotesAttribute()
    {
        return $this->visit_reason;
    }

    /**
     * Backward compatibility: allow old code to set notes.
     */
    public function setNotesAttribute($value): void
    {
        $this->attributes['visit_reason'] = is_array($value) ? json_encode($value) : $value;
    }
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            // Ensure UUID
            if (!$model->id) {
                $model->id = (string) Str::uuid();
            }

            if ($model->availability_id) {
                $availability = \App\Models\DoctorAvailability::find($model->availability_id);
                if ($availability) {
                    $override = $model->availability_override_id
                        ? \App\Models\DoctorAvailabilityOverride::find($model->availability_override_id)
                        : null;

                    $model->appointment_date = $availability->date ?? $model->appointment_date;
                    $model->appointment_time = $override?->start_time ?? $model->appointment_time ?? $availability->start_time;
                    $model->appointment_end_time = $override?->end_time ?? $model->appointment_end_time ?? $availability->end_time;
                }
            }

            // Generate professional slug if not set
            if (empty($model->slug)) {
                $slugParts = ['appointment'];

                if ($model->doctor_id) {
                    $doctor = $model->relationLoaded('doctor')
                        ? $model->doctor
                        : \App\Models\Doctor::find($model->doctor_id);
                    if ($doctor) {
                        $slugParts[] = Str::slug($doctor->first_name . '-' . $doctor->last_name);
                    }
                }

                if ($model->appointment_date) {
                    $slugParts[] = \Carbon\Carbon::parse($model->appointment_date)->format('Y-m-d');
                }

                $slugParts[] = Str::lower(Str::random(3));
                $model->slug = implode('-', $slugParts);
            }

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
                $model->save();
            }
        });
    }

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function doctor()
    {
        return $this->belongsTo(Doctor::class);
    }

    /**
     * Get the doctor who replaced the original doctor (if any)
     */
    public function replacedByDoctor()
    {
        return $this->belongsTo(Doctor::class, 'replaced_by_id');
    }

    public function availability()
    {
        return $this->belongsTo(\App\Models\DoctorAvailability::class, 'availability_id');
    }

    public function availabilityOverride()
    {
        return $this->belongsTo(\App\Models\DoctorAvailabilityOverride::class, 'availability_override_id');
    }

    public function prescriptions()
    {
        return $this->hasMany(Prescription::class);
    }

    public function medicalReports()
    {
        return $this->hasMany(MedicalReport::class);
    }

    public function payment()
    {
        return $this->hasOne(Payment::class);
    }

    public function paymentWaiver()
    {
        return $this->belongsTo(User::class, 'payment_waived_by');
    }

    public function videoConsultation()
    {
        return $this->hasOne(VideoConsultation::class);
    }
    public function doctorReviews()
    {
        return $this->hasMany(DoctorReview::class, 'appointment_id');
    }


    /**
     * Get previous appointments for the same patient with the same doctor
     */
    public function previousAppointments()
    {
        return $this->hasMany(Appointment::class, 'patient_id', 'patient_id')
            ->where('doctor_id', $this->doctor_id)
            ->whereKeyNot($this->id)
            ->whereIn('status', [
                AppointmentStatus::COMPLETED,
                AppointmentStatus::CANCELLED,
            ])
            ->orderBy('appointment_date', 'desc')
            ->limit(5);
    }

    /**
     * Get the effective doctor (active doctor for this appointment)
     * Since doctor_id always points to the active doctor, we just return it
     */
    public function getEffectiveDoctorAttribute()
    {
        return $this->doctor;
    }

    /**
     * Get the original doctor (before replacement)
     * When replaced: doctor_id = replacement, replaced_by_id = replacement
     * When not replaced: doctor_id = original, replaced_by_id = null
     */
    public function getOriginalDoctorAttribute()
    {
        // If replaced, find the replacement record to get original doctor
        if ($this->replaced_by_id) {
            // Find replacement record where replacement_doctor_id matches current doctor_id
            $replacement = DoctorReplacement::where('replacement_doctor_id', $this->doctor_id)
                ->where('is_active', true)
                ->where(function ($q) {
                    $q->whereNull('start_date')
                        ->orWhereDate('start_date', '<=', $this->appointment_date);
                })
                ->where(function ($q) {
                    $q->whereNull('end_date')
                        ->orWhereDate('end_date', '>=', $this->appointment_date);
                })
                ->first();

            if ($replacement) {
                return $replacement->originalDoctor;
            }
        }

        // Otherwise, doctor_id is the original doctor
        return $this->doctor;
    }

    /**
     * Check if appointment has been replaced
     */
    public function hasBeenReplaced(): bool
    {
        return !empty($this->replaced_by_id);
    }

    /**
     * Check if appointment has active replacement
     */
    public function hasActiveReplacement(): bool
    {
        return DoctorReplacement::where('original_doctor_id', $this->doctor_id)
            ->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('start_date')
                    ->orWhereDate('start_date', '<=', $this->appointment_date);
            })
            ->where(function ($q) {
                $q->whereNull('end_date')
                    ->orWhereDate('end_date', '>=', $this->appointment_date);
            })
            ->exists();
    }

    /**
     * Get active replacement for this appointment
     */
    public function getActiveReplacement()
    {
        return DoctorReplacement::where('original_doctor_id', $this->doctor_id)
            ->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('start_date')
                    ->orWhereDate('start_date', '<=', $this->appointment_date);
            })
            ->where(function ($q) {
                $q->whereNull('end_date')
                    ->orWhereDate('end_date', '>=', $this->appointment_date);
            })
            ->first();
    }

    public static function canUserAccess(): bool
    {
        $user = \Illuminate\Support\Facades\Auth::user();
        return $user && (
            $user->hasRole('super_admin') ||
            $user->hasRole('doctor_manager') ||
            $user->can('appointments.view') ||
            $user->can('appointments.view_any') ||
            $user->hasRole('doctor') ||
            $user->hasRole('receptionist')
        );
    }

    public function scopeVisibleTo($query, $user = null)
    {
        $user = $user ?? \Illuminate\Support\Facades\Auth::user();

        if (!$user) return $query->whereRaw('1 = 0');

        if ($user->hasRole('super_admin') || $user->hasRole('doctor_manager') || $user->hasRole('receptionist') || $user->can('appointments.view') || $user->can('appointments.view_any')) {
            return $query;
        }

        if ($user->hasRole('doctor')) {
            return $query->where('doctor_id', function ($q) use ($user) {
                $q->select('id')->from('doctors')->where('user_id', $user->id);
            });
        }

        return $query->whereRaw('1 = 0');
    }

    public function scopeWithoutTestDoctors($query)
    {
        return $query->whereHas('doctor', function ($query) {
            $query->withoutTestDoctors();
        });
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
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
     * Get the Whereby room URL associated with this appointment.
     */
    public function getWherebyRoomUrlAttribute(): ?string
    {
        return $this->videoConsultation?->room_url;
    }

    /**
     * Get the Whereby room ID associated with this appointment.
     */
    public function getWherebyRoomIdAttribute(): ?string
    {
        return $this->videoConsultation?->room_id;
    }
}
