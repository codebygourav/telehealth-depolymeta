<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class Patient extends Model
{
    use \App\Traits\InteractsWithModuleDocuments, \App\Traits\SyncsWithUser;
    use HasFactory, SoftDeletes;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'user_id',
        'slug',
        'first_name',
        'last_name',
        'gender',
        'date_of_birth',
        'age',
        'bio',
        'father_name',
        'wife_name',
        'husband_name',
        'mobile_no',
        'alternate_no',
        'email',
        'address',
        'pincode',
        'area',
        'city',
        'landmark',
        'state',
        'nationality',
        'marital_status',
        'partner_relation_type',
        'spouse_name',
        'blood_group',
        'allergies',
        'existing_conditions',
        'current_medications',
        'past_medical_history',
        'emergency_contact_name',
        'emergency_contact_relationship',
        'emergency_contact_phone',
        'insurance_provider',
        'insurance_policy_number',
        'insurance_policy_expiry',
        'insurance_tpa_details',
        'treatment_consent_accepted',
        'source',
        'is_existing_patient',
        'existing_patient_id',
        'create_user_account',
        'avatar',
    ];

    protected $casts = [
        'is_existing_patient' => 'boolean',
        'create_user_account' => 'boolean',
        'treatment_consent_accepted' => 'boolean',
        'insurance_policy_expiry' => 'date',
    ];

    protected $appends = ['avatar'];

    protected $moduleDocumentKeys = ['avatar'];

    /**
     * Fallback for avatar attribute if not found in module_documents for Patient.
     * Checks user's avatar as fallback.
     */
    public function getAvatarFallback()
    {
        return $this->user?->avatar;
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (! $model->id) {
                $model->id = (string) Str::uuid();
            }
            // Always generate unique slug if first_name and last_name found (or just first_name)
            if (! empty($model->first_name)) {
                $slugParts = [$model->first_name];
                if (! empty($model->last_name)) {
                    $slugParts[] = $model->last_name;
                }
                $baseSlug = Str::slug(implode(' ', $slugParts));
                $slug = $baseSlug;
                $counter = 1;

                // Query to check if the slug already exists (ignoring the current model)
                while (
                    static::where('slug', $slug)->exists()
                ) {
                    $slug = $baseSlug . '-' . $counter;
                    $counter++;
                }
                $model->slug = $slug;
            }

            // Set created_by, updated_by for audit trail if not set (requires auth)
            if (Auth::check()) {
                if (empty($model->created_by)) {
                    $model->created_by = Auth::user()->id;
                }
                if (Auth::user()->id == $model->id) {
                    $model->deleted_by = Auth::user()->id;
                }
                if (empty($model->updated_by)) {
                    $model->updated_by = Auth::user()->id;
                }
            }
        });

        static::updating(function ($model) {
            // Always update slug if first_name and last_name found (or just first_name)
            if (! empty($model->first_name)) {
                $slugParts = [$model->first_name];
                if (! empty($model->last_name)) {
                    $slugParts[] = $model->last_name;
                }
                $baseSlug = Str::slug(implode(' ', $slugParts));
                $slug = $baseSlug;
                $counter = 1;

                // Query to check if the slug already exists (excluding current model)
                while (
                    static::where('slug', $slug)
                    ->where('id', '!=', $model->id)
                    ->exists()
                ) {
                    $slug = $baseSlug . '-' . $counter;
                    $counter++;
                }

                $model->slug = $slug;
            }

            if (Auth::check()) {
                $model->updated_by = Auth::user()->id;
            }
        });

        static::deleting(function ($model) {
            if (Auth::check()) {
                $model->deleted_by = Auth::user()->id;
                $model->save();
            }

            // Soft delete the associated user
            if ($model->user_id) {
                $model->user()->delete();
            }
        });

        static::restoring(function ($model) {
            // Restore the associated user
            if ($model->user_id) {
                // We use withTrashed() because the user is already soft-deleted
                $model->user()->withTrashed()->restore();
            }
        });
    }

    public static function canUserAccess(): bool
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        return $user && (
            $user->hasRole('super_admin') ||
            $user->hasRole('patient_manager') ||
            $user->can('patients.view') ||
            $user->can('patients.view_any') ||
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
        if (
            $user->hasRole('super_admin') ||
            $user->hasRole('patient_manager') ||
            $user->can('patients.view') ||
            $user->can('patients.view_any')
        ) {
            return $query;
        }
        if ($user->hasRole('doctor')) {
            // Optionally, filter by user_id
            return $query->where('user_id', $user->id);
        }

        return $query->whereRaw('1 = 0');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function appointments()
    {
        return $this->hasMany(Appointment::class);
    }

    public function previousAppointments()
    {
        return $this->hasMany(Appointment::class)
            ->whereIn('status', [
                \App\Enums\AppointmentStatus::COMPLETED,
                \App\Enums\AppointmentStatus::CANCELLED,
            ])
            ->orderBy('appointment_date', 'desc')
            ->limit(5);
    }

    public function prescriptions()
    {
        return $this->hasMany(Prescription::class);
    }

    public function medicalReports()
    {
        return $this->hasMany(MedicalReport::class);
    }

    /**
     * Calculate age from date of birth
     */
    public function getAgeAttribute(): ?int
    {
        if ($this->date_of_birth) {
            return \Carbon\Carbon::parse($this->date_of_birth)->age;
        }

        return $this->attributes['age'] ?? null;
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
}
