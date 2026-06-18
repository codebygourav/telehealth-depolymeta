<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class PatientVaccinationLog extends Model
{
    use HasFactory;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'patient_vaccination_id',
        'performed_by_id',
        'action',
        'old_value',
        'new_value',
        'reason',
    ];

    protected static function booted(): void
    {
        static::creating(function (PatientVaccinationLog $log) {
            if (! $log->getKey()) {
                $log->{$log->getKeyName()} = (string) Str::uuid();
            }
        });
    }

    public function patientVaccination(): BelongsTo
    {
        return $this->belongsTo(PatientVaccination::class);
    }

    public function performedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'performed_by_id');
    }
}
