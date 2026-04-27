<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use App\Traits\InteractsWithModuleDocuments;
use App\Models\Patient;
use App\Models\FakerPatient;

class DoctorReview extends Model
{
    use SoftDeletes, InteractsWithModuleDocuments;

    protected $table = 'doctor_reviews';

    protected $fillable = [
        'patient_id',
        'doctor_id',
        'appointment_id',
        'review_type',
        'faker_patient_id',
        'title',
        'content',
        'rating',
        'is_active',
        'is_featured',
        'slug',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected $appends = ['patient_image', 'patient_name'];
    protected $moduleDocumentKeys = [];

    public $incrementing = false;
    protected $keyType = 'string';

    protected $casts = [
        'rating' => 'integer',
        'is_active' => 'boolean',
        'is_featured' => 'boolean',
    ];

    protected static function booted()
    {
        static::creating(function ($review) {
            // Generate UUID if not set
            if (!$review->getKey()) {
                $review->{$review->getKeyName()} = (string) Str::uuid();
            }

            // Generate slug if not set - limit to 80 characters for better URLs
            if (empty($review->slug)) {
                $patientName = 'patient';
                if ($review->review_type === 'fake' && $review->faker_patient_id) {
                    $fakerPatient = $review->fakerPatient ?? FakerPatient::find($review->faker_patient_id);
                    $patientName = $fakerPatient ? $fakerPatient->name : 'patient';
                } else {
                    $patient = $review->patient ?? ($review->patient_id ? Patient::find($review->patient_id) : null);
                    $patientName = $patient ? ($patient->first_name . ' ' . $patient->last_name) : 'patient';
                }
                $patientSlug = Str::slug($patientName);
                $titleSlug = Str::slug($review->title);

                // Limit title to 50 characters to keep slug manageable
                $titleSlug = Str::limit($titleSlug, 50, '');

                // Combine: patient-name-title (max 80 chars total)
                $slugBase = $patientSlug . '-' . $titleSlug;

                // Ensure total length doesn't exceed 80 characters
                if (strlen($slugBase) > 80) {
                    $maxPatientLength = min(strlen($patientSlug), 30);
                    $maxTitleLength = 80 - $maxPatientLength - 1; // -1 for the dash
                    $slugBase = Str::limit($patientSlug, $maxPatientLength, '') . '-' . Str::limit($titleSlug, $maxTitleLength, '');
                }

                $originalSlug = $slugBase;
                $count = 1;

                while (static::where('slug', $slugBase)->exists()) {
                    // Add suffix if duplicate, but keep total under 100 chars
                    $suffix = '-' . $count;
                    $maxBaseLength = 100 - strlen($suffix);
                    $slugBase = Str::limit($originalSlug, $maxBaseLength, '') . $suffix;
                    $count++;
                }

                $review->slug = $slugBase;
            }

            // Set created_by and updated_by
            if (Auth::check()) {
                $review->created_by = Auth::id();
                $review->updated_by = Auth::id();
            }
        });

        static::updating(function ($review) {
            // Update slug if patient_id, faker_patient_id, review_type or title changed - limit to 80 characters
            if ($review->isDirty(['patient_id', 'faker_patient_id', 'review_type', 'title'])) {
                $patientName = 'patient';
                if ($review->review_type === 'fake' && $review->faker_patient_id) {
                    $fakerPatient = $review->fakerPatient ?? FakerPatient::find($review->faker_patient_id);
                    $patientName = $fakerPatient ? $fakerPatient->name : 'patient';
                } else {
                    $patient = $review->patient ?? ($review->patient_id ? Patient::find($review->patient_id) : null);
                    $patientName = $patient ? ($patient->first_name . ' ' . $patient->last_name) : 'patient';
                }
                $patientSlug = Str::slug($patientName);
                $titleSlug = Str::slug($review->title);

                // Limit title to 50 characters to keep slug manageable
                $titleSlug = Str::limit($titleSlug, 50, '');

                // Combine: patient-name-title (max 80 chars total)
                $slugBase = $patientSlug . '-' . $titleSlug;

                // Ensure total length doesn't exceed 80 characters
                if (strlen($slugBase) > 80) {
                    $maxPatientLength = min(strlen($patientSlug), 30);
                    $maxTitleLength = 80 - $maxPatientLength - 1; // -1 for the dash
                    $slugBase = Str::limit($patientSlug, $maxPatientLength, '') . '-' . Str::limit($titleSlug, $maxTitleLength, '');
                }

                $originalSlug = $slugBase;
                $count = 1;

                while (static::where('slug', $slugBase)->where('id', '!=', $review->id)->exists()) {
                    // Add suffix if duplicate, but keep total under 100 chars
                    $suffix = '-' . $count;
                    $maxBaseLength = 100 - strlen($suffix);
                    $slugBase = Str::limit($originalSlug, $maxBaseLength, '') . $suffix;
                    $count++;
                }

                $review->slug = $slugBase;
            }

            // Set updated_by
            if (Auth::check()) {
                $review->updated_by = Auth::id();
            }
        });

        static::deleting(function ($review) {
            // Set deleted_by before soft delete
            if (Auth::check()) {
                $review->deleted_by = Auth::id();
                $review->save();
            }
        });
    }

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function doctor()
    {
        return $this->belongsTo(Doctor::class);
    }

    public function fakerPatient()
    {
        return $this->belongsTo(FakerPatient::class, 'faker_patient_id');
    }
    public function appointment()
    {
        return $this->belongsTo(Appointment::class, 'appointment_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function deleter()
    {
        return $this->belongsTo(User::class, 'deleted_by');
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    public function scopeForDoctor($query, $doctorId)
    {
        return $query->where('doctor_id', $doctorId);
    }

    /**
     * Get patient name from patient relationship or faker patient
     * This is a computed attribute that gets the name from the patient relationship or faker patient
     */
    public function getPatientNameAttribute()
    {
        // If it's a fake review, get name from faker patient
        if ($this->review_type === 'fake' && $this->faker_patient_id) {
            $fakerPatient = $this->relationLoaded('fakerPatient') ? $this->fakerPatient : FakerPatient::find($this->faker_patient_id);
            return $fakerPatient ? $fakerPatient->name : null;
        }

        // Otherwise, get from original patient
        if (!$this->patient_id) {
            return null;
        }

        // Load patient if not already loaded
        $patient = $this->relationLoaded('patient') ? $this->patient : $this->patient;

        if (!$patient) {
            $patient = Patient::find($this->patient_id);
        }

        if (!$patient) {
            return null;
        }

        return $patient->first_name . ' ' . $patient->last_name;
    }

    public function getPatientImageAttribute()
    {
        // If it's a fake review, get avatar from faker patient
        if ($this->review_type === 'fake' && $this->faker_patient_id) {
            $fakerPatient = $this->relationLoaded('fakerPatient') ? $this->fakerPatient : FakerPatient::find($this->faker_patient_id);
            return $fakerPatient ? $fakerPatient->avatar : null;
        }

        // Otherwise, get from original patient
        if (!$this->patient_id) {
            return null;
        }

        // Load patient with user relationship if not already loaded
        $patient = $this->relationLoaded('patient') ? $this->patient : $this->patient;

        if (!$patient) {
            $patient = Patient::with('user')->find($this->patient_id);
        }

        if (!$patient) {
            return null;
        }

        // Get avatar from patient (checks module_documents first, then user avatar)
        return $patient->avatar;
    }
}