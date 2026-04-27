<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use App\Models\ModuleDocument;

class DepartmentTab extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'id',
        'department_id',
        'tab_title',
        'tab_content',
        'order',
        'tab_gallery', // Virtual attribute
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected $appends = ['tab_gallery'];

    protected $pending_tab_gallery = '__NOT_SET__';

    public $incrementing = false;
    protected $keyType = 'string';

    protected static function booted()
    {
        static::creating(function ($model) {
            if (empty($model->id)) {
                $model->id = (string) Str::uuid();
            }

            if (Auth::check()) {
                $model->created_by = Auth::id();
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

        static::saved(function ($model) {
            if ($model->pending_tab_gallery !== '__NOT_SET__') {
                $files = $model->pending_tab_gallery;

                if (!empty($files)) {
                    $model->moduleDocuments()->updateOrCreate(
                        ['name' => 'tab_gallery'],
                        ['files' => $files] // Changed from file_name to files
                    );
                } else {
                    $model->moduleDocuments()->where('name', 'tab_gallery')->delete();
                }

                $model->pending_tab_gallery = '__NOT_SET__';
            }
        });
    }

    public function setTabGalleryAttribute($value)
    {
        $this->pending_tab_gallery = $value;
    }

    // Media collections removed

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function moduleDocuments()
    {
        return $this->morphMany(ModuleDocument::class, 'model');
    }

    public function getTabGalleryAttribute()
    {
        $val = $this->moduleDocuments()->where('name', 'tab_gallery')->first()?->files;
        return is_array($val) ? $val : [];
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
