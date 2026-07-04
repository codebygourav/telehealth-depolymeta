<?php

namespace App\Models;

use App\Enums\DisplayEventCategory;
use App\Enums\DisplayMediaType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\SoftDeletes;

class DisplayEvent extends Model
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
        'category',
        'media_type',
        'media_url',
        'image',
        'link',
        'is_active',
        'display_order',
        'starts_at',
        'ends_at',
        'autoplay',
        'loop',
        'muted',
        'open_in_new_tab',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'display_order' => 'integer',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'category' => DisplayEventCategory::class,
        'autoplay' => 'boolean',
        'loop' => 'boolean',
        'muted' => 'boolean',
        'open_in_new_tab' => 'boolean',
    ];

    protected static function booted()
    {
        static::saving(function ($event) {
            $category = $event->category;
            $mediaType = DisplayMediaType::normalize($event->media_type);

            if ($category) {
                // If the category & media type do not support an image field, clear the image.
                if (! $category->showsImageField($mediaType)) {
                    $event->image = null;
                }

                // If the category & media type do not support a link field, clear both link and media_url.
                if (! $category->showsLinkField($mediaType)) {
                    $event->link = null;
                    $event->media_url = null;
                } else {
                    // Sync link to media_url so they stay in step when a link is supported
                    $event->media_url = $event->link;
                }
            }
        });

        static::creating(function ($event) {
            // Generate UUID if not set
            if (empty($event->id)) {
                $event->id = (string) Str::uuid();
            }

            if (!$event->slug) {
                $event->slug = Str::slug($event->title);
            }

            // Handle duplicate slugs
            $originalSlug = $event->slug;
            $count = 1;
            while (static::where('slug', $event->slug)->exists()) {
                $event->slug = $originalSlug . '-' . $count++;
            }

            // Save the creating user's UUID if available
            if (Auth::check()) {
                $event->created_by = Auth::id();
            }
        });

        static::updating(function ($event) {
            // Save the updater user's UUID if available
            if (Auth::check()) {
                $event->updated_by = Auth::id();
            }
        });

        static::deleting(function ($event) {
            // Save the deleter user's UUID if available
            if (Auth::check()) {
                $event->deleted_by = Auth::id();
                $event->save(); // Ensure deleted_by is saved before soft delete
            }
        });
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
        return $this->belongsToMany(Doctor::class, 'display_event_doctor', 'display_event_id', 'doctor_id')->withTimestamps();
    }

    public function getCategoryLabelAttribute(): string
    {
        if ($this->category instanceof DisplayEventCategory) {
            return $this->category->label();
        }

        return $this->category ? str((string) $this->category)->replace('_', ' ')->title()->toString() : 'Advertisement';
    }

    /**
     * Override savePendingModuleDocuments to save files in display_events folder
     */
    public function savePendingModuleDocuments()
    {
        foreach ($this->pendingModuleDocuments as $key => $file) {
            $this->moduleDocuments()->where('name', $key)->delete();

            if ($file) {
                $filePath = $file;
                if (is_string($file) && !str_contains($file, 'display_events/')) {
                    $fileName = basename($file);
                    $filePath = 'display_events/' . $fileName;
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
