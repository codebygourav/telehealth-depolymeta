<?php

namespace App\Models;

use App\Enums\VaccinationProgramTargetType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class VaccinationProgram extends Model
{
    use HasFactory;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'name',
        'slug',
        'description',
        'target_type',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'target_type' => VaccinationProgramTargetType::class,
    ];

    protected static function booted(): void
    {
        static::creating(function (VaccinationProgram $program) {
            if (! $program->getKey()) {
                $program->{$program->getKeyName()} = (string) Str::uuid();
            }

            if (empty($program->slug) && ! empty($program->name)) {
                $program->slug = Str::slug($program->name);
            }
        });
    }

    public function templates(): HasMany
    {
        return $this->hasMany(VaccinationTemplate::class);
    }
}
