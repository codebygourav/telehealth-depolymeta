<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class VaccinationTemplate extends Model
{
    use HasFactory, SoftDeletes;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'doctor_id',
        'name',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::creating(function (VaccinationTemplate $template) {
            if (! $template->getKey()) {
                $template->{$template->getKeyName()} = (string) Str::uuid();
            }
        });
    }

    public function items(): HasMany
    {
        return $this->hasMany(VaccinationTemplateItem::class)
            ->orderBy('set_sort_order')
            ->orderBy('sort_order');
    }

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class);
    }

    public function patientVaccinations(): HasMany
    {
        return $this->hasMany(PatientVaccination::class);
    }
}
