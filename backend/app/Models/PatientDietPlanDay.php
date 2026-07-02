<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class PatientDietPlanDay extends Model
{
    use HasFactory;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'patient_diet_plan_id',
        'day_number',
        'week_day',
        'date',
    ];

    protected $casts = [
        'day_number' => 'integer',
        'date' => 'date',
    ];

    protected static function booted(): void
    {
        static::creating(function (PatientDietPlanDay $day) {
            if (! $day->getKey()) {
                $day->{$day->getKeyName()} = (string) Str::uuid();
            }
        });
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(PatientDietPlan::class, 'patient_diet_plan_id');
    }
    

    public function meals(): HasMany
    {
        return $this->hasMany(PatientDietPlanMeal::class, 'patient_diet_plan_day_id')
            ->orderBy('sort_order');
    }
}