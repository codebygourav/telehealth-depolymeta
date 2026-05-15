<?php

namespace App\Models;

use App\Enums\VaccinationDocumentType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class VaccinationDocument extends Model
{
    use HasFactory;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'patient_vaccination_id',
        'document',
        'document_type',
        'certificate_number',
    ];

    protected $casts = [
        'document_type' => VaccinationDocumentType::class,
    ];

    protected static function booted(): void
    {
        static::creating(function (VaccinationDocument $document) {
            if (! $document->getKey()) {
                $document->{$document->getKeyName()} = (string) Str::uuid();
            }
        });
    }

    public function patientVaccination(): BelongsTo
    {
        return $this->belongsTo(PatientVaccination::class);
    }
}
