<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;

class Prescription extends Model
{
    use SoftDeletes;

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'appointment_id',
        'doctor_id',
        'patient_id',
        'medicine_id',
        'doctor_added_medicine_id',
        'medicine_name',
        'medicine_type',
        'strength',
        'dosage',
        'frequency',
        'frequency_times',
        'duration',
        'duration_type',
        'duration_value',
        'instructions',
        'meal_timing',
        'route',
        'application_area',
        'is_sos',
        'sos_instruction',
        'remarks',
        'quantity',
        'start_date',
        'end_date',
        'is_ongoing',
        'order',
        'created_by',
        'updated_by',
        'deleted_by',
        'use_type',
        'take_when',
        'min_gap',
        'max_doses_per_day',
        'patient_instruction',
    ];

    protected $hidden = [
        'created_by',
        'updated_by',
        'deleted_by',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    protected $casts = [
        'frequency_times' => 'array',
        'is_ongoing' => 'boolean',
        'is_sos' => 'boolean',
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (!$model->id) {
                $model->id = (string) Str::uuid();
            }

            if (Auth::check()) {
                $model->created_by = Auth::id();
            }
        });

        static::created(function (Prescription $prescription) {
            if ($prescription->appointment) {
                try {
                    \App\Services\NotificationService::notifyPrescriptionAdded($prescription->appointment);
                } catch (\Exception $e) {
                    \Illuminate\Support\Facades\Log::error("Failed to send prescription notification: " . $e->getMessage());
                }
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

    public function appointment()
    {
        return $this->belongsTo(Appointment::class, 'appointment_id');
    }


    public function doctor()
    {
        return $this->belongsTo(Doctor::class, 'doctor_id');
    }


    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    // App\Models\Prescription.php

    public function medicine()
    {
        return $this->belongsTo(Medicine::class, 'medicine_id');
    }

    public function doctorAddedMedicine()
    {
        return $this->belongsTo(DoctorAddedMedicine::class, 'doctor_added_medicine_id');
    }


    /**
     * Get formatted frequency with times
     */
    public function getFormattedFrequencyAttribute(): string
    {
        $frequency = $this->frequency ?? '';
        if ($this->frequency_times && is_array($this->frequency_times)) {
            $times = implode(', ', $this->frequency_times);
            return "{$frequency}\n{$times}";
        }
        return $frequency;
    }

    /**
     * Get formatted duration
     */
    public function getFormattedDurationAttribute(): string
    {
        if ($this->is_ongoing) {
            return 'Daily, ongoing';
        }

        if ($this->duration_value && $this->duration_type) {
            return "{$this->duration_value} {$this->duration_type}";
        }

        return $this->duration ?? '';
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
}
