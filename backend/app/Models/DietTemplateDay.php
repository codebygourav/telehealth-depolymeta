<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class DietTemplateDay extends Model
{
    use HasFactory;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'diet_template_id',
        'day_number',
        'week_day',
    ];

    protected $casts = [
        'day_number' => 'integer',
    ];

    protected static function booted(): void
    {
        static::creating(function (DietTemplateDay $day) {
            if (! $day->getKey()) {
                $day->{$day->getKeyName()} = (string) Str::uuid();
            }
        });
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(DietTemplate::class, 'diet_template_id');
    }

    public function meals(): HasMany
    {
        return $this->hasMany(DietTemplateMeal::class, 'diet_template_day_id')
            ->orderBy('sort_order');
    }
}
