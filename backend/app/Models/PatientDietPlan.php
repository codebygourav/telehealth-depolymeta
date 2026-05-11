<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class PatientDietPlan extends Model
{
    use HasFactory, SoftDeletes;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'patient_id',
        'doctor_id',
        'diet_template_id',
        'template_name',
        'template_description',
        'duration_days',
        'start_date',
        'end_date',
        'status',
        'special_instructions',
    ];

    protected $casts = [
        'duration_days' => 'integer',
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    protected static function booted(): void
    {
        static::creating(function (PatientDietPlan $plan) {
            if (! $plan->getKey()) {
                $plan->{$plan->getKeyName()} = (string) Str::uuid();
            }
        });
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class);
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(DietTemplate::class, 'diet_template_id');
    }

    public function days(): HasMany
    {
        return $this->hasMany(PatientDietPlanDay::class)
            ->orderBy('day_number');
    }
}
