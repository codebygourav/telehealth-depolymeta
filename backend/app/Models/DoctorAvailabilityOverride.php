<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;

class DoctorAvailabilityOverride extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'doctor_availability_id',
        'doctor_id',
        'override_date',
        'start_time',
        'end_time',
        'capacity',
        'consultation_fee',
        'doctor_room',
        'status',
        'note',
        'booking_cutoff_rules',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected $casts = [
        'override_date' => 'date',
        'start_time' => 'datetime:H:i',
        'end_time' => 'datetime:H:i',
        'capacity' => 'integer',
        'consultation_fee' => 'decimal:2',
        'booking_cutoff_rules' => 'array',
    ];

    protected $attributes = [
        'status' => 'active',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $override) {
            if (Auth::check()) {
                $override->created_by = Auth::id();
            }
        });

        static::updating(function (self $override) {
            if (Auth::check()) {
                $override->updated_by = Auth::id();
            }
        });

        static::deleting(function (self $override) {
            if (Auth::check() && ! $override->isForceDeleting()) {
                $override->deleted_by = Auth::id();
                $override->save();
            }
        });
    }

    public function availability(): BelongsTo
    {
        return $this->belongsTo(DoctorAvailability::class, 'doctor_availability_id');
    }

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
