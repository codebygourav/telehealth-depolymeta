<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;

class Symptom extends Model
{
    use SoftDeletes, HasFactory;
    use \App\Traits\InteractsWithModuleDocuments;

    protected $fillable = [
        'name',
        'description',
        'slug',
        'featured_image',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    public $incrementing = false;
    protected $keyType = 'string';

    protected $appends = ['featured_image'];
    protected $moduleDocumentKeys = ['featured_image'];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (!$model->getKey()) {
                $model->{$model->getKeyName()} = (string) Str::uuid();
            }

            if (empty($model->slug) && !empty($model->name)) {
                $model->slug = Str::slug($model->name);
            }

            if (Auth::check()) {
                $model->created_by = Auth::id();
            }
        });

        static::updating(function ($model) {
            if (array_key_exists('name', $model->getDirty()) && !empty($model->name)) {
                $model->slug = Str::slug($model->name);
            }

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

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /** 🔥 Scope for Filament Visibility */
    public function scopeVisibleTo($query, $user)
    {
        if ($user->hasRole('super_admin') || $user->can('symptoms.view_any')) {
            return $query;
        }

        // otherwise show nothing
        return $query->whereRaw('1=0');
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
