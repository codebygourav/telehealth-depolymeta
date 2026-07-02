<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\SoftDeletes;

class Advertisement extends Model
{
    use SoftDeletes;
    use \App\Traits\InteractsWithModuleDocuments;

    public $incrementing = false;
    protected $keyType = 'string';

    protected $appends = ['image'];
    protected $moduleDocumentKeys = ['image'];

    protected $fillable = [
        'id',
        'title',
        'slug',
        'description',
        'image',
        'link',
        'media_type',
        'media_url',
        'placement',
        'display_order',
        'starts_at',
        'ends_at',
        'autoplay',
        'loop',
        'muted',
        'open_in_new_tab',
        'is_active',
        'published_at',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected static function booted()
    {
        static::creating(function ($advertisement) {
            // Generate UUID if not set
            if (empty($advertisement->id)) {
                $advertisement->id = (string) Str::uuid();
            }

            if (!$advertisement->slug) {
                $advertisement->slug = Str::slug($advertisement->title);
            }

            // Handle duplicate slugs
            $originalSlug = $advertisement->slug;
            $count = 1;
            while (static::where('slug', $advertisement->slug)->exists()) {
                $advertisement->slug = $originalSlug . '-' . $count++;
            }

            // Save the creating user's UUID if available
            if (Auth::check()) {
                $advertisement->created_by = Auth::id();
            }
        });

        static::updating(function ($advertisement) {
            // Save the updater user's UUID if available
            if (Auth::check()) {
                $advertisement->updated_by = Auth::id();
            }
        });

        static::deleting(function ($advertisement) {
            // Save the deleter user's UUID if available
            if (Auth::check()) {
                $advertisement->deleted_by = Auth::id();
                $advertisement->save(); // Ensure deleted_by is saved before soft delete
            }
        });
    }

    public function scopeVisibleTo(\Illuminate\Database\Eloquent\Builder $query, $user = null): \Illuminate\Database\Eloquent\Builder
    {
        $user = $user ?? Auth::user();
        if (!$user)
            return $query->whereRaw('1 = 0');

        // Admins/Managers can see everything
        if ($user->hasRole('super_admin') || $user->hasRole('doctor_manager') || $user->can('advertisements.view_any')) {
            return $query;
        }

        // Active advertisements only for others
        return $query->where('is_active', true);
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

    public function doctors()
    {
        return $this->belongsToMany(Doctor::class, 'advertisement_doctor', 'advertisement_id', 'doctor_id')->withTimestamps();
    }

    /**
     * Override savePendingModuleDocuments to save files in advertisement folder
     */
    public function savePendingModuleDocuments()
    {
        foreach ($this->pendingModuleDocuments as $key => $file) {
            $this->moduleDocuments()->where('name', $key)->delete();

            if ($file) {
                // Ensure file path includes advertisement folder
                $filePath = $file;
                if (is_string($file) && !str_contains($file, 'advertisements/')) {
                    // If file doesn't have the folder, prepend it
                    $fileName = basename($file);
                    $filePath = 'advertisements/' . $fileName;
                }

                $this->moduleDocuments()->create([
                    'name' => $key,
                    'files' => [$filePath],
                ]);
            }
        }

        $this->pendingModuleDocuments = [];
    }
}
