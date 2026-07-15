<?php


namespace App\Models;

use App\Enums\GenderOption;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class Doctor extends Model
{
    use \App\Traits\InteractsWithModuleDocuments, \App\Traits\SyncsWithUser;
    use HasFactory, SoftDeletes;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'user_id',
        'google_sheet_doctor_id',
        'first_name',
        'last_name',
        'dob',
        'gender',
        'name',
        'marital_status',
        'blood_group',
        'area',
        'qualification',
        'years_experience',
        'career_start_year',
        'medical_license_number',
        'address_line1',
        'address_line2',
        'country',
        'state',
        'city',
        'pincode',
        'landmark',
        'bio',
        'sub_title',
        'description',
        'languages_known',
        'social_links',
        'education_info',
        'awards_info',
        'professional_experience_info',
        'certifications_info',
        'fellowships_info',
        'specializations_info',
        'key_procedures_info',
        'expertise_info',
        'special_interests',
        'availability_info',
        'memberships_info',
        'status',
        'hide_from_mobile_app',
        'hide_from_wordpress_api',
        'is_test_doctor',
        'voice_name',
        'speech_rate',
        'speech_pitch',
        'speech_locale',
        'ai_training_profile',
        'slug',
        'email_sent',
        'created_by',
        'updated_by',
        'deleted_by',
        'avatar',
        'signature',
        'is_checked_in',
        'checked_in_at',
        'is_on_break',
    ];

    protected $appends = ['avatar', 'signature'];

    protected $moduleDocumentKeys = ['avatar', 'signature'];


    /**
     * Fallback for avatar attribute if not found in module_documents for Doctor.
     */
    public function getAvatarFallback()
    {
        return $this->getRawOriginal('avatar') ?: $this->user?->avatar;
    }

    /**
     * Fallback for signature attribute if not found in module_documents for Doctor.
     */
    public function getSignatureFallback()
    {
        return $this->getRawOriginal('signature');
    }

    protected $casts = [
        'email_sent' => 'boolean',
        'hide_from_mobile_app' => 'boolean',
        'hide_from_wordpress_api' => 'boolean',
        'is_test_doctor' => 'boolean',
        'years_experience' => 'integer',
        'education_info' => 'array',
        'awards_info' => 'array',
        'professional_experience_info' => 'array',
        'fellowships_info' => 'array',
        'certifications_info' => 'array',
        'languages_known' => 'array',
        'social_links' => 'array',
        'gender' => GenderOption::class,
        'status' => \App\Enums\DoctorStatus::class,
        'is_checked_in' => 'boolean',
        'is_on_break' => 'boolean',
        'checked_in_at' => 'datetime',
        'speech_rate' => 'double',
        'speech_pitch' => 'double',
        'ai_training_profile' => 'array',
    ];

    public function externalBookings()
    {
        return $this->hasMany(ExternalBooking::class);
    }

    protected static function booted()
    {
        static::creating(function ($doctor) {
            if (! $doctor->getKey()) {
                $doctor->{$doctor->getKeyName()} = (string) Str::uuid();
            }


            $maxNumber = (int) \Illuminate\Support\Facades\DB::table('doctors')
                ->selectRaw('MAX(CAST(SUBSTRING(doctor_code, 3) AS UNSIGNED)) as max_code')
                ->value('max_code');

            $nextNumber = $maxNumber ? ($maxNumber + 1) : 1;

            $candidate = 'DR' . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
            while (self::where('doctor_code', $candidate)->exists()) {
                $nextNumber++;
                $candidate = 'DR' . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
            }
            $doctor->doctor_code = $candidate;

            $baseSlug = $doctor->user
                ? Str::slug($doctor->user->name)
                : Str::slug(trim($doctor->first_name . '-' . $doctor->last_name, '- '));

            $slug = $baseSlug;
            $counter = 1;
            while (self::where('slug', $slug)->where('id', '!=', $doctor->id)->exists()) {
                $slug = $baseSlug . '-' . $counter++;
            }
            $doctor->slug = $slug;
            $doctor->slug = $slug;

            // Automatically set created_by and updated_by on creation
            if (Auth::check()) {
                $doctor->created_by = Auth::id();
                $doctor->updated_by = Auth::id();
            }
        });

        static::saving(function ($doctor) {
            if ($doctor->career_start_year) {
                $doctor->years_experience =
                    now()->year - (int) $doctor->career_start_year;
            }
        });
        static::updating(function ($doctor) {
            if ($doctor->isDirty(['first_name', 'last_name'])) {
                $baseSlug = Str::slug(trim($doctor->first_name . ' ' . $doctor->last_name));
                $slug = $baseSlug;
                $counter = 1;

                while (self::where('slug', $slug)
                    ->where('id', '!=', $doctor->id)
                    ->exists()
                ) {
                    $slug = $baseSlug . '-' . $counter++;
                }

                $doctor->slug = $slug;
            }

            // Automatically set updated_by on update
            if (Auth::check()) {
                $doctor->updated_by = Auth::id();
            }
        });

        static::deleting(function ($doctor) {
            // Automatically set deleted_by on delete
            if (Auth::check() && ! $doctor->isForceDeleting()) {
                $doctor->deleted_by = Auth::id();
                $doctor->save();
            }

            // Soft delete the associated user (only if not force deleting)
            if ($doctor->user_id && ! $doctor->isForceDeleting()) {
                $doctor->user()->delete();
            }
        });

        static::forceDeleting(function ($doctor) {
            // Force delete the associated user
            if ($doctor->user_id) {
                $user = \App\Models\User::withTrashed()->find($doctor->user_id);
                if ($user) {
                    $user->forceDelete();
                }
            }
        });

        static::restoring(function ($doctor) {
            // Restore the associated user
            if ($doctor->user_id) {
                // We use withTrashed() because the user is already soft-deleted
                $doctor->user()->withTrashed()->restore();
            }
        });

        static::saved(function ($doctor) {
            if ($doctor->wasChanged(['hide_from_mobile_app', 'hide_from_wordpress_api', 'is_test_doctor', 'status'])) {
                \Illuminate\Support\Facades\Cache::forget('home_doctors');
                \Illuminate\Support\Facades\Cache::forget('home_patient_reviews');
            }

            // Get current array lengths
            $certificationsCount = is_array($doctor->certifications_info) ? count($doctor->certifications_info) : 0;
            $awardsCount = is_array($doctor->awards_info) ? count($doctor->awards_info) : 0;

            // Process certifications_info images and save to module_documents
            if ($doctor->certifications_info && is_array($doctor->certifications_info)) {
                foreach ($doctor->certifications_info as $index => $certification) {
                    $docName = "certification_{$index}_image";
                    $existingDoc = $doctor->moduleDocuments()->withTrashed()->where('name', $docName)->first();

                    if (isset($certification['certification_image']) && $certification['certification_image']) {
                        $imagePath = $certification['certification_image'];

                        // Ensure the path includes doctorDocument folder
                        if (is_string($imagePath) && ! str_contains($imagePath, 'doctorDocument/')) {
                            $fileName = basename($imagePath);
                            $imagePath = 'doctorDocument/' . $fileName;
                        }

                        if ($existingDoc) {
                            if ($existingDoc->trashed()) {
                                $existingDoc->restore();
                            }
                            $existingDoc->update([
                                'files' => [$imagePath],
                                'updated_by' => \Illuminate\Support\Facades\Auth::id(),
                            ]);
                        } else {
                            $doctor->moduleDocuments()->create([
                                'name' => $docName,
                                'files' => [$imagePath],
                            ]);
                        }
                    } else {
                        // If image is removed, delete the module document
                        if ($existingDoc) {
                            $existingDoc->delete();
                        }
                    }
                }
            }

            // Clean up module documents for removed certification entries
            $doctor->moduleDocuments()
                ->where('name', 'like', 'certification_%_image')
                ->get()
                ->each(function ($doc) use ($certificationsCount) {
                    preg_match('/certification_(\d+)_image/', $doc->name, $matches);
                    if (isset($matches[1]) && (int) $matches[1] >= $certificationsCount) {
                        $doc->delete();
                    }
                });

            // Process awards_info images and save to module_documents
            if ($doctor->awards_info && is_array($doctor->awards_info)) {
                foreach ($doctor->awards_info as $index => $award) {
                    $docName = "award_{$index}_image";
                    $existingDoc = $doctor->moduleDocuments()->withTrashed()->where('name', $docName)->first();

                    if (isset($award['award_image']) && $award['award_image']) {
                        $imagePath = $award['award_image'];

                        // Ensure the path includes doctorDocument folder
                        if (is_string($imagePath) && ! str_contains($imagePath, 'doctorDocument/')) {
                            $fileName = basename($imagePath);
                            $imagePath = 'doctorDocument/' . $fileName;
                        }

                        if ($existingDoc) {
                            if ($existingDoc->trashed()) {
                                $existingDoc->restore();
                            }
                            $existingDoc->update([
                                'files' => [$imagePath],
                                'updated_by' => \Illuminate\Support\Facades\Auth::id(),
                            ]);
                        } else {
                            $doctor->moduleDocuments()->create([
                                'name' => $docName,
                                'files' => [$imagePath],
                            ]);
                        }
                    } else {
                        // If image is removed, delete the module document
                        if ($existingDoc) {
                            $existingDoc->delete();
                        }
                    }
                }
            }

            // Clean up module documents for removed award entries
            $doctor->moduleDocuments()
                ->where('name', 'like', 'award_%_image')
                ->get()
                ->each(function ($doc) use ($awardsCount) {
                    preg_match('/award_(\d+)_image/', $doc->name, $matches);
                    if (isset($matches[1]) && (int) $matches[1] >= $awardsCount) {
                        $doc->delete();
                    }
                });
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function departments()
    {
        return $this->belongsToMany(
            Department::class,
            'department_doctor',
            'doctor_id',
            'department_id'
        )->withPivot('role', 'order')->withTimestamps();
    }

    public function availabilities()
    {
        return $this->hasMany(DoctorAvailability::class);
    }

    public function availabilityOverrides()
    {
        return $this->hasMany(DoctorAvailabilityOverride::class);
    }

    public function reviews()
    {
        return $this->hasMany(\App\Models\DoctorReview::class, 'doctor_id');
    }

    public function replacements()
    {
        return $this->hasMany(DoctorReplacement::class, 'original_doctor_id');
    }

    public static function canUserAccess(): bool
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        return $user && (
            $user->hasRole('super_admin') ||
            $user->hasRole('doctor_manager') ||
            $user->can('doctors.view') ||
            $user->can('doctors.view_any') ||
            $user->hasRole('doctor')
        );
    }

    public function scopeVisibleTo(\Illuminate\Database\Eloquent\Builder $query, $user = null): \Illuminate\Database\Eloquent\Builder
    {
        /** @var \App\Models\User $user */
        $user = $user ?? Auth::user();
        if (! $user) {
            return $query->whereRaw('1 = 0');
        }
        if ($user->hasRole('super_admin') || $user->hasRole('doctor_manager') || $user->can('doctors.view') || $user->can('doctors.view_any')) {
            return $query;
        }
        if ($user->hasRole('doctor')) {
            return $query->where('user_id', $user->id);
        }

        return $query->whereRaw('1 = 0');
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by', 'id');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by', 'id');
    }

    public function deleter()
    {
        return $this->belongsTo(User::class, 'deleted_by', 'id');
    }

    /**
     * Scope a query to only include active doctors.
     */
    public function scopeActive($query)
    {
        return $query->where('status', \App\Enums\DoctorStatus::ACTIVE->value);
    }

    /**
     * Scope doctors that can be shown in the patient mobile app.
     *
     * Hidden doctors are excluded from general browse/search. When a patient is
     * provided, a hidden doctor can still be loaded if that patient has an
     * appointment with the doctor.
     */
    public function scopeVisibleInMobileApp($query, ?Patient $patient = null, bool $includeBookedHiddenDoctors = false)
    {
        return $query->where(function ($query) use ($patient, $includeBookedHiddenDoctors) {
            $query->where('hide_from_mobile_app', false)
                ->orWhereNull('hide_from_mobile_app');

            if (! $includeBookedHiddenDoctors || ! $patient) {
                return;
            }

            $query->orWhereExists(function ($appointmentQuery) use ($patient) {
                $appointmentQuery->selectRaw('1')
                    ->from('appointments')
                    ->whereColumn('appointments.doctor_id', 'doctors.id')
                    ->where('appointments.patient_id', $patient->id)
                    ->whereDate('appointments.appointment_date', '>=', now()->toDateString())
                    ->whereIn('appointments.status', [
                        \App\Enums\AppointmentStatus::CONFIRMED->value,
                        \App\Enums\AppointmentStatus::RESCHEDULED->value,
                    ])
                    ->whereNull('appointments.deleted_at');
            });
        });
    }

    public function scopeVisibleInWordPressApi($query)
    {
        return $query->where(function ($query) {
            $query->where('hide_from_wordpress_api', false)
                ->orWhereNull('hide_from_wordpress_api');
        });
    }

    public function scopeWithoutTestDoctors($query)
    {
        return $query->where(function ($query) {
            $query->where('is_test_doctor', false)
                ->orWhereNull('is_test_doctor');
        });
    }

    /**
     * Scope a query to include doctors with available slots in a date range.
     */
    public function scopeWithAvailability($query, $startDate, $endDate)
    {
        return $query->whereExists(function ($q) use ($startDate, $endDate) {
            $q->selectRaw('1')
                ->from('availabilities')
                ->whereColumn('availabilities.doctor_id', 'doctors.id')
                ->where('is_available', true)
                ->whereNull('deleted_at')
                ->where(function ($subQ) use ($startDate, $endDate) {
                    $subQ->where(function ($q) use ($startDate, $endDate) {
                        $q->where('is_recurring', false)
                            ->where('date', '>=', $startDate)
                            ->where('date', '<=', $endDate);
                    })
                        ->orWhere(function ($q) use ($startDate, $endDate) {
                            $q->where('is_recurring', true)
                                ->where(function ($query) use ($endDate) {
                                    $query->whereNull('recurring_start_date')
                                        ->orWhere('recurring_start_date', '<=', $endDate);
                                })
                                ->where(function ($query) use ($startDate) {
                                    $query->whereNull('recurring_end_date')
                                        ->orWhere('recurring_end_date', '>=', $startDate);
                                });
                        })
                        ->orWhere(function ($q) {
                            $q->where('is_recurring', false)
                                ->whereNull('date')
                                ->whereNotNull('day_of_week');
                        });
                });
        });
    }
}
