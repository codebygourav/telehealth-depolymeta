<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class DietTemplate extends Model
{
    use HasFactory, SoftDeletes;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'doctor_id',
        'name',
        'description',
        'duration_days',
        'restrictions',
        'notes',
        'is_active',
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
        'is_active' => 'boolean',
        'features' => 'array',
    ];

    protected static function booted(): void
    {
        static::creating(function (DietTemplate $template) {
            if (! $template->getKey()) {
                $template->{$template->getKeyName()} = (string) Str::uuid();
            }
        });
    }

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class)->withTrashed();
    }

    public function days(): HasMany
    {
        return $this->hasMany(DietTemplateDay::class)
            ->orderBy('day_number');
    }
}
