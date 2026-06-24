<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
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
        'diet_category',
        'patient_type',
        'daily_calories',
        'protein_target',
        'carbs_limit',
        'salt_limit',
        'doctor_remark',
        'allowed_food_notes',
        'hydration_advice',
        'exercise_advice',
        'features',
    ];

    protected $casts = [
        'duration_days' => 'integer',
        'start_date' => 'date',
        'end_date' => 'date',
        'features' => 'array',
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

    public function meals(): HasManyThrough
    {
        return $this->hasManyThrough(
            PatientDietPlanMeal::class,
            PatientDietPlanDay::class,
            'patient_diet_plan_id',
            'patient_diet_plan_day_id'
        );
    }
}
