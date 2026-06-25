<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class PatientDietPlanMealCompletion extends Model
{
    use HasFactory;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'patient_diet_plan_meal_id',
        'occurrence_date',
        'status',
        'completed_by_role',
        'completed_by_name',
        'notes',
        'completed_at',
    ];

    protected $casts = [
        'occurrence_date' => 'date',
        'completed_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (PatientDietPlanMealCompletion $completion) {
            if (! $completion->getKey()) {
                $completion->{$completion->getKeyName()} = (string) Str::uuid();
            }
        });
    }

    public function meal(): BelongsTo
    {
        return $this->belongsTo(PatientDietPlanMeal::class, 'patient_diet_plan_meal_id');
    }
}
