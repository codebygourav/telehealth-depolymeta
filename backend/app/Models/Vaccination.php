<?php

namespace App\Models;

use App\Enums\VaccinationGenderRestriction;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Vaccination extends Model
{
    use HasFactory, SoftDeletes;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'name',
        'short_name',
        'manufacturer',
        'disease_for',
        'description',
        'side_effects',
        'contraindications',
        'precautions',
        'dosage_information',
        'is_multi_dose',
        'total_doses',
        'minimum_age_days',
        'maximum_age_days',
        'gender_restriction',
        'is_active'
    ];

    protected $casts = [
        'is_multi_dose' => 'boolean',
        'is_active' => 'boolean',
        'total_doses' => 'integer',
        'minimum_age_days' => 'integer',
        'maximum_age_days' => 'integer',
        'gender_restriction' => VaccinationGenderRestriction::class,
    ];

    protected static function booted(): void
    {
        static::creating(function (Vaccination $vaccination) {
            if (! $vaccination->getKey()) {
                $vaccination->{$vaccination->getKeyName()} = (string) Str::uuid();
            }
        });
    }

    public function patientVaccinations(): HasMany
    {
        return $this->hasMany(PatientVaccination::class);
    }

    public function templateItems(): HasMany
    {
        return $this->hasMany(VaccinationTemplateItem::class);
    }

    public function faqs(): HasMany
    {
        return $this->hasMany(VaccinationFaq::class)->orderBy('sort_order');
    }
}
