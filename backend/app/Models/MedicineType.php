<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;

class MedicineType extends Model
{
    use SoftDeletes;

    protected $table = 'medicine_types';

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'name',
        'slug',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->{$model->getKeyName()})) {
                $model->{$model->getKeyName()} = (string) Str::uuid();
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
    }

    public function medicines()
    {
        return $this->hasMany(Medicine::class, 'type_id');
    }

    // Auto-generate slug
    public function setNameAttribute($value)
    {
        $this->attributes['name'] = $value;

        if (!isset($this->attributes['slug']) || empty($this->attributes['slug'])) {
            $this->attributes['slug'] = Str::slug($value);
        }
    }

    public function fill(array $attributes)
    {
        if (isset($attributes['name']) && (!isset($attributes['slug']) || empty($attributes['slug']))) {
            $attributes['slug'] = Str::slug($attributes['name']);
        }
        return parent::fill($attributes);
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /** Access control (for navigation & actions) */
    public static function canUserAccess(): bool
    {
        $user = auth()->user();
        return $user &&
            (
                $user->hasRole('super_admin') ||
                $user->can('manage_medicine_types')
            );
    }

    /** Scope for table filtering */
    public function scopeVisibleTo($query, $user)
    {
        if (!$user) return $query->whereRaw('0=1');

        if (
            $user->hasRole('super_admin') ||
            $user->can('view_any_medicine_types')
        ) {
            return $query;
        }

        return $query->whereRaw('0=1');
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