<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use App\Traits\InteractsWithModuleDocuments;

class FakerPatient extends Model
{
    use SoftDeletes, InteractsWithModuleDocuments;

    protected $table = 'fake_reviewers';

    protected $fillable = [
        'name',
        'age',
        'address',
        'avatar',
    ];

    protected $appends = ['avatar'];
    protected $moduleDocumentKeys = ['avatar'];

    public $incrementing = false;
    protected $keyType = 'string';

    protected $casts = [
        'age' => 'integer',
    ];

    protected static function booted()
    {
        static::creating(function ($fakerPatient) {
            // Generate UUID if not set
            if (!$fakerPatient->getKey()) {
                $fakerPatient->{$fakerPatient->getKeyName()} = (string) Str::uuid();
            }
        });
    }

    /**
     * Get the doctor reviews for this faker patient
     */
    public function doctorReviews()
    {
        return $this->hasMany(DoctorReview::class, 'faker_patient_id');
    }
}