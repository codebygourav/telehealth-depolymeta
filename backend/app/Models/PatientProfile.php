<?php

namespace App\Models;

use App\Enums\PatientProfileType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class PatientProfile extends Model
{
    use HasFactory, SoftDeletes;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'patient_id',
        'name',
        'profile_type',
        'date_of_birth',
        'gender',
        'pregnancy_due_date',
        'blood_group',
        'weight',
        'height',
        'is_primary',
    ];

    protected $casts = [
        'date_of_birth' => 'date',
        'pregnancy_due_date' => 'date',
        'weight' => 'decimal:2',
        'height' => 'decimal:2',
        'is_primary' => 'boolean',
        'profile_type' => PatientProfileType::class,
    ];

    protected static function booted(): void
    {
        static::creating(function (PatientProfile $profile) {
            if (! $profile->getKey()) {
                $profile->{$profile->getKeyName()} = (string) Str::uuid();
            }
        });

        static::saving(function (PatientProfile $profile) {
            if ($profile->is_primary) {
                static::where('patient_id', $profile->patient_id)
                    ->when($profile->exists, fn ($query) => $query->where('id', '!=', $profile->id))
                    ->update(['is_primary' => false]);
            }

            if ($profile->profile_type === PatientProfileType::SELF) {
                $profile->loadMissing('patient');
                $patient = $profile->patient;
                if ($patient) {
                    $profile->name = trim("{$patient->first_name} {$patient->last_name}") ?: 'Self';
                }
            }
        });

        static::saved(function (PatientProfile $profile) {
            if ($profile->profile_type !== PatientProfileType::SELF) {
                return;
            }

            $profile->loadMissing('patient');
            $patient = $profile->patient;
            if (! $patient) {
                return;
            }

            $patient->update([
                'gender' => $profile->gender,
                'date_of_birth' => $profile->date_of_birth,
                'blood_group' => $profile->blood_group,
                'weight' => $profile->weight,
                'height' => $profile->height,
            ]);
        });
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function vaccinations(): HasMany
    {
        return $this->hasMany(PatientVaccination::class);
    }

    public function vaccinationPrograms(): HasMany
    {
        return $this->hasMany(PatientVaccinationProgram::class);
    }
}
