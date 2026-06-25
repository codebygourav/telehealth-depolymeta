<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class PatientDietPlanMeal extends Model
{
    use HasFactory;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'patient_diet_plan_day_id',
        'meal_type',
        'meal_name',
        'instructions',
        'meal_image',
        'helpful_links',
        'calories',
        'protein_grams',
        'carbs_grams',
        'fat_grams',
        'meal_time',
        'status',
        'notes',
        'completed_at',
        'sort_order',
    ];

    protected $casts = [
        'helpful_links' => 'array',
        'calories' => 'integer',
        'protein_grams' => 'integer',
        'carbs_grams' => 'integer',
        'fat_grams' => 'integer',
        'completed_at' => 'datetime',
        'sort_order' => 'integer',
    ];

    protected static function booted(): void
    {
        static::creating(function (PatientDietPlanMeal $meal) {
            if (! $meal->getKey()) {
                $meal->{$meal->getKeyName()} = (string) Str::uuid();
            }
        });
    }

    public function planDay(): BelongsTo
    {
        return $this->belongsTo(PatientDietPlanDay::class, 'patient_diet_plan_day_id');
    }

    public function completions(): HasMany
    {
        return $this->hasMany(PatientDietPlanMealCompletion::class, 'patient_diet_plan_meal_id');
    }
}
