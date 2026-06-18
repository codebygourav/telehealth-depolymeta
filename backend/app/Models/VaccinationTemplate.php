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
        'vaccination_program_id',
        'doctor_id',
        'name',
        'description',
        'is_active',
        'reminder_1_days_before',
        'reminder_2_days_before',
        'reminder_3_days_before',
        'overdue_alert_days_after',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'reminder_1_days_before' => 'integer',
        'reminder_2_days_before' => 'integer',
        'reminder_3_days_before' => 'integer',
        'overdue_alert_days_after' => 'integer',
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
        return $this->belongsTo(Doctor::class)->withTrashed();
    }

    public function program(): BelongsTo
    {
        return $this->belongsTo(VaccinationProgram::class, 'vaccination_program_id');
    }

    public function patientVaccinations(): HasMany
    {
        return $this->hasMany(PatientVaccination::class);
    }
}
