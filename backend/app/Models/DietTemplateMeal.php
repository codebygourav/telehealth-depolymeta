<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class DietTemplateMeal extends Model
{
    use HasFactory;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'diet_template_day_id',
        'meal_type',
        'meal_name',
        'instructions',
        'meal_image',
        'helpful_links',
        'calories',
        'protein_grams',
        'carbs_grams',
        'fat_grams',
        'start_time',
        'sort_order',
    ];

    protected $casts = [
        'helpful_links' => 'array',
        'calories' => 'integer',
        'protein_grams' => 'integer',
        'carbs_grams' => 'integer',
        'fat_grams' => 'integer',
        'sort_order' => 'integer',
    ];

    protected static function booted(): void
    {
        static::creating(function (DietTemplateMeal $meal) {
            if (! $meal->getKey()) {
                $meal->{$meal->getKeyName()} = (string) Str::uuid();
            }
        });
    }

    public function day(): BelongsTo
    {
        return $this->belongsTo(DietTemplateDay::class, 'diet_template_day_id');
    }
}
