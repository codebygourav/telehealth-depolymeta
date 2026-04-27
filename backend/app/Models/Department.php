<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Illuminate\Support\Facades\Log; // Debugging
use App\Models\ModuleDocument;

class Department extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'departments';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'name',
        'status',
        'slug',
        'description',
        'additional_information',
        'faqs',
        'publications',
        'symptom_ids',
        'created_by',
        'updated_by',
        'deleted_by',
        'department_featured',
        // removed 'department_featured' from fillable because it is not a real field in the DB
        'is_tab_layout',
        'department_featured', // Virtual attribute
        'department_stamp', // Virtual attribute
    ];

    protected $appends = ['department_featured', 'department_stamp'];

    // Temporary storage for images before save
    protected $pending_featured_image = '__NOT_SET__';
    protected $pending_stamp_image = '__NOT_SET__';

    protected $casts = [
        'additional_information' => 'array',
        'faqs' => 'array',
        'publications' => 'array',
        'symptom_ids' => 'array',
        'is_tab_layout' => 'boolean',
    ];


    public function doctors()
    {
        return $this->belongsToMany(
            Doctor::class,
            'department_doctor',
            'department_id',
            'doctor_id'
        )->withPivot('role', 'order')->withTimestamps();
    }

    // Media collections removed

    public function moduleDocuments()
    {
        return $this->morphMany(ModuleDocument::class, 'model');
    }

    public function getDepartmentFeaturedAttribute()
    {
        $files = $this->moduleDocuments()->where('name', 'featured_image')->first()?->files;
        return is_array($files) && !empty($files) ? $files[0] : null;
    }

    public function setDepartmentFeaturedAttribute($value)
    {
        $this->pending_featured_image = $value;
    }

    public function getDepartmentStampAttribute()
    {
        $files = $this->moduleDocuments()->where('name', 'department_stamp')->first()?->files;
        return is_array($files) && !empty($files) ? $files[0] : null;
    }

    public function setDepartmentStampAttribute($value)
    {
        $this->pending_stamp_image = $value;
    }

    public function tabs()
    {
        return $this->hasMany(DepartmentTab::class)->orderBy('order');
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
    public function getSymptomsAttribute()
    {
        return $this->symptom_ids;
    }
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->id)) {
                $model->id = (string) Str::uuid();
            }

            $user = auth()->user();
            if ($user) {
                $model->created_by = $user->id;
            }

            // Automatically generate unique slug based on name
            $baseSlug = Str::slug($model->name);
            $slug = $baseSlug;
            $counter = 1;
            // Ensure slug is unique
            while (
                self::where('slug', $slug)
                ->where('id', '!=', $model->id)
                ->exists()
            ) {
                $slug = $baseSlug . '-' . $counter++;
            }

            $model->slug = $slug;
        });
        // Generate a unique department code like DP001, DP002, etc.
        static::creating(function ($model) {
            // Only assign code if not already set in the 'code' column
            if (empty($model->code)) {
                // Find the max existing code number from the 'code' column
                $latestModel = self::where('code', 'LIKE', 'DP%')
                    ->orderByDesc('created_at')
                    ->first();

                if ($latestModel && !empty($latestModel->code) && preg_match('/DP(\d+)/', $latestModel->code, $matches)) {
                    $nextNumber = intval($matches[1]) + 1;
                } else {
                    $nextNumber = 1;
                }

                $model->code = 'DP' . str_pad($nextNumber, 3, '0', STR_PAD_LEFT);
            }
        });

        static::saved(function ($model) {
            if ($model->pending_featured_image !== '__NOT_SET__') {
                $file = $model->pending_featured_image;

                // Clear existing
                $model->moduleDocuments()->where('name', 'featured_image')->delete();

                if ($file) {
                    $model->moduleDocuments()->create([
                        'name' => 'featured_image',
                        'files' => [$file],
                    ]);
                }

                $model->pending_featured_image = '__NOT_SET__';
            }

            if ($model->pending_stamp_image !== '__NOT_SET__') {
                $file = $model->pending_stamp_image;

                // Clear existing
                $model->moduleDocuments()->where('name', 'department_stamp')->delete();

                if ($file) {
                    $model->moduleDocuments()->create([
                        'name' => 'department_stamp',
                        'files' => [$file],
                    ]);
                }

                $model->pending_stamp_image = '__NOT_SET__';
            }
        });
    }


    public function scopeVisibleTo($query, $user)
    {
        if (!$user) {
            return $query->whereRaw('1=0');
        }

        if ($user->hasRole('super_admin') || $user->can('departments.view_any')) {
            return $query;
        }

        if ($user->can('departments.view')) {
            return $query;
        }

        return $query->whereRaw('1=0');
    }
}
