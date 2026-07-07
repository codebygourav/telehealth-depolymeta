<?php

namespace App\Models;


use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use App\Traits\InteractsWithModuleDocuments;

use App\Enums\MedicalReportStatus;

class MedicalReport extends Model
{
    use SoftDeletes, InteractsWithModuleDocuments, HasFactory;

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'appointment_id',
        'patient_id',
        'doctor_id',
        'name',
        'type',
        'description',
        'report_date',
        'file_path',
        'file_name',
        'file_type',
        'is_public',
        'results',
        'notes',
        'status',
        'is_shared',
        'created_by',
        'updated_by',
        'deleted_by',
        'uploader_id',
        'uploader_type',
    ];

    protected $casts = [
        'report_date' => 'date',
        'results' => 'array',
        'is_public' => 'boolean',
        'is_shared' => 'boolean',
        'status' => MedicalReportStatus::class,
    ];

    protected $moduleDocumentKeys = ['file'];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (!$model->id) {
                $model->id = (string) Str::uuid();
            }

            if (Auth::check()) {
                $model->created_by = Auth::id();

                // Automatically set uploader if the authenticated user is a doctor or patient
                $user = Auth::user();
                if ($user->doctor) {
                    $model->uploader_id = $user->doctor->id;
                    $model->uploader_type = 'Doctor';
                } elseif ($user->patient) {
                    $model->uploader_id = $user->patient->id;
                    $model->uploader_type = 'Patient';
                }
            }
        });

        static::updating(function ($model) {
            if (Auth::check()) {
                $model->updated_by = Auth::id();
            }
        });

        static::deleting(function ($model) {
            if (Auth::check()) {
                $model->deleted_by = Auth::id();
                $model->save();
            }
        });
    }

    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class);
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class);
    }

    public function uploader()
    {
        return $this->morphTo();
    }

    public function getUploaderNameAttribute(): string
    {
        if (!$this->uploader) {
            return 'Unknown';
        }

        if ($this->uploader_type === 'Doctor' || $this->uploader instanceof Doctor) {
            return ($this->uploader->user?->name ?? 'Unknown');
        }

        if ($this->uploader_type === 'Patient' || $this->uploader instanceof Patient) {
            return ($this->uploader->first_name ?? '') . ' ' . ($this->uploader->last_name ?? '');
        }

        return 'Unknown';
    }

    /**
     * Override savePendingModuleDocuments to save files in medical_report folder
     */
    protected function savePendingModuleDocuments()
    {
        foreach ($this->pendingModuleDocuments as $key => $file) {
            $this->moduleDocuments()->where('name', $key)->delete();

            if ($file) {
                // Ensure file path includes medical_report folder
                $filePath = $file;
                if (is_string($file) && !str_contains($file, 'medical_report/')) {
                    // If file doesn't have the folder, prepend it
                    $fileName = basename($file);
                    $filePath = 'medical_report/' . $fileName;
                }

                $this->moduleDocuments()->create([
                    'name' => $key,
                    'files' => [$filePath],
                ]);
            }
        }

        $this->pendingModuleDocuments = [];
    }

    /**
     * Get the file URL - checks module_documents first, then falls back to file_path
     *
     * @return string|null
     */
    public function getFileUrlAttribute(): ?string
    {
        // First check module_documents
        $file = $this->file;
        if ($file) {
            // If file is a full URL, return it
            if (str_starts_with($file, 'http://') || str_starts_with($file, 'https://')) {
                return $file;
            }

            // If file is in medical_report folder or has a path, construct URL
            $filePath = str_starts_with($file, 'medical_report/') ? $file : 'medical_report/' . basename($file);

            if (Storage::disk('public')->exists($filePath)) {
                /** @var \Illuminate\Filesystem\FilesystemAdapter $disk */
                $disk = Storage::disk('public');
                return $disk->url($filePath);
            }

            // Try direct path
            if (Storage::disk('public')->exists($file)) {
                /** @var \Illuminate\Filesystem\FilesystemAdapter $disk */
                $disk = Storage::disk('public');
                return $disk->url($file);
            }
        }

        // Fallback to old file_path column for backward compatibility
        if ($this->file_path && Storage::disk('public')->exists($this->file_path)) {
            /** @var \Illuminate\Filesystem\FilesystemAdapter $disk */
            $disk = Storage::disk('public');
            return $disk->url($this->file_path);
        }

        return null;
    }

    /**
     * Fallback for file attribute if not found in module_documents
     */
    public function getFileFallback()
    {
        // Return old file_path if exists
        return $this->file_path;
    }

    /**
     * Get type label
     */
    public function getTypeLabelAttribute(): string
    {
        return match ($this->type) {
            'lab_report' => 'Lab Report',
            'radiology' => 'Radiology',
            'prescription' => 'Prescription',
            'other' => 'Other',
            default => ucfirst($this->type ?? 'Unknown'),
        };
    }
    public function canUserAccess(): bool
    {
        $user = auth()->user();
        return $user && (
            $user->hasRole('super_admin') ||
            $user->hasRole('doctor_manager') ||
            $user->can('medical_reports.view') ||
            $user->can('medical_reports.view_any')
        );
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
     * Check if the report is owned by or associated with a user
     */
    public function isOwnedBy($user): bool
    {
        if (!$user) return false;

        // Super admins and managers can see all
        if ($user->hasRole('super_admin') || $user->hasRole('doctor_manager')) {
            return true;
        }

        // Creator can see
        if ($this->created_by === $user->id) {
            return true;
        }

        // If user is a doctor, they can see reports assigned to them
        if ($user->hasRole('doctor')) {
            $doctor = $user->doctor;
            if ($doctor && $this->doctor_id === $doctor->id) {
                return true;
            }
        }

        // If user is a patient, they can see their own reports
        if ($user->hasRole('patient')) {
            $patient = $user->patient;
            if ($patient && $this->patient_id === $patient->id) {
                return true;
            }
        }

        return false;
    }

    /**
     * Scope a query to only include reports owned by or associated with a user
     */
    public function scopeOwnedBy(\Illuminate\Database\Eloquent\Builder $query, $user): \Illuminate\Database\Eloquent\Builder
    {
        if (!$user) {
            return $query->whereRaw('1 = 0');
        }

        if ($user->hasRole('super_admin') || $user->hasRole('doctor_manager')) {
            return $query;
        }

        return $query->where(function ($q) use ($user) {
            $q->where('created_by', $user->id);

            if ($user->hasRole('doctor')) {
                $doctor = $user->doctor;
                if ($doctor) {
                    $q->orWhere('doctor_id', $doctor->id);
                }
            }

            if ($user->hasRole('patient')) {
                $patient = $user->patient;
                if ($patient) {
                    $q->orWhere('patient_id', $patient->id);
                }
            }
        });
    }
}