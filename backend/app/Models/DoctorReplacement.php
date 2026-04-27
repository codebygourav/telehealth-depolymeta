<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;

class DoctorReplacement extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'original_doctor_id',
        'replacement_doctor_id',
        'replaced_by',
        'replacement_type',
        'start_date',
        'end_date',
        'reason',
        'notes',
        'is_active',
    ];

    public $incrementing = false;
    protected $keyType = 'string';

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'is_active' => 'boolean',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (!$model->id) {
                $model->id = (string) Str::uuid();
            }
            if (Auth::check()) {
                $model->replaced_by = $model->replaced_by ?? Auth::id();
            }
        });
    }

    public function originalDoctor()
    {
        return $this->belongsTo(Doctor::class, 'original_doctor_id');
    }

    public function replacementDoctor()
    {
        return $this->belongsTo(Doctor::class, 'replacement_doctor_id');
    }

    public function replacedByUser()
    {
        return $this->belongsTo(User::class, 'replaced_by');
    }

    /**
     * Check if replacement is active for a given date
     */
    public function isActiveForDate($date): bool
    {
        if (!$this->is_active) {
            return false;
        }

        $checkDate = \Carbon\Carbon::parse($date)->format('Y-m-d');

        // Check date range
        if ($this->start_date && $checkDate < $this->start_date->format('Y-m-d')) {
            return false;
        }

        if ($this->end_date && $checkDate > $this->end_date->format('Y-m-d')) {
            return false;
        }

        return true;
    }

    /**
     * Get appointments affected by this replacement
     * Returns appointments where doctor_id was changed to replacement_doctor_id
     */
    public function affectedAppointments()
    {
        // Find appointments where doctor_id matches replacement_doctor_id (meaning they were replaced)
        $query = Appointment::where('doctor_id', $this->replacement_doctor_id)
            ->whereNotIn('status', ['cancelled', 'completed']);

        if ($this->start_date) {
            $query->whereDate('appointment_date', '>=', $this->start_date);
        }

        if ($this->end_date) {
            $query->whereDate('appointment_date', '<=', $this->end_date);
        }

        return $query;
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForDate($query, $date)
    {
        return $query->where(function ($q) use ($date) {
            $q->whereNull('start_date')
                ->orWhere('start_date', '<=', $date);
        })->where(function ($q) use ($date) {
            $q->whereNull('end_date')
                ->orWhere('end_date', '>=', $date);
        });
    }

    /**
     * Get transferred availabilities for this replacement
     */
    public function transferredAvailabilities()
    {
        return DoctorAvailability::where('doctor_id', $this->replacement_doctor_id)
            ->where('created_at', '>=', $this->created_at)
            ->when($this->start_date, function ($query) {
                $query->where(function ($q) {
                    $q->where('is_recurring', false)
                        ->where('date', '>=', $this->start_date)
                        ->orWhere('is_recurring', true);
                });
            })
            ->when($this->end_date, function ($query) {
                $query->where(function ($q) {
                    $q->where('is_recurring', false)
                        ->where('date', '<=', $this->end_date)
                        ->orWhere(function ($sq) {
                            $sq->where('is_recurring', true)
                                ->where('recurring_end_date', '>=', $this->start_date);
                        });
                });
            });
    }

    /**
     * Get count of transferred availabilities
     */
    public function getTransferredAvailabilitiesCountAttribute()
    {
        return $this->transferredAvailabilities()->count();
    }

    /**
     * Check if replacement affects availability
     */
    public function affectsAvailability(): bool
    {
        return in_array($this->replacement_type, ['all', 'permanent']);
    }

    /**
     * Get replacement service instance
     */
    protected function getReplacementService()
    {
        return app(\App\Services\DoctorReplacementService::class);
    }

    /**
     * Revert this replacement
     */
    public function revert(): void
    {
        $this->getReplacementService()->revertReplacement($this);
    }

    /**
     * Get effective doctor for a specific date (considering this replacement)
     */
    public function getEffectiveDoctorForDate(string $date): ?Doctor
    {
        if (!$this->is_active) {
            return null;
        }

        $checkDate = \Carbon\Carbon::parse($date)->format('Y-m-d');

        if ($this->start_date && $checkDate < $this->start_date->format('Y-m-d')) {
            return null;
        }

        if ($this->end_date && $checkDate > $this->end_date->format('Y-m-d')) {
            return null;
        }

        return $this->replacementDoctor;
    }
}
