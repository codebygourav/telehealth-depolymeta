<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;

class Medicine extends Model
{
    use SoftDeletes;

    protected $table = 'medicines';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'name',
        'slug',
        'category_id',
        'type_id',
        'hospital_stock',
        'quantity',
        'batch_number',
        'manufactured_date',
        'expiry_date',
        'manufacturer',
        'price',
        'description',
        'strength_options',
        'dosage_options',
        'frequency_options',
        'timing_options',
        'meal_options',
        'route_options',
        'duration_options',
        'application_area_options',
        'field_rules',
        'spoken_aliases',
        'default_strength',
        'default_dosage',
        'default_frequency',
        'default_timing',
        'default_meal',
        'default_duration',
        'default_route',
        'default_instructions',
        'speech_enabled',
        'is_active',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected $casts = [
        'strength_options' => 'array',
        'dosage_options' => 'array',
        'frequency_options' => 'array',
        'timing_options' => 'array',
        'meal_options' => 'array',
        'route_options' => 'array',
        'duration_options' => 'array',
        'application_area_options' => 'array',
        'field_rules' => 'array',
        'spoken_aliases' => 'array',
        'speech_enabled' => 'boolean',
        'is_active' => 'boolean',
    ];

    // Relations
    public function category()
    {
        return $this->belongsTo(MedicineCategory::class, 'category_id');
    }

    public function type()
    {
        return $this->belongsTo(MedicineType::class, 'type_id');
    }

    // Auto-generate UUID
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->{$model->getKeyName()})) {
                $model->{$model->getKeyName()} = (string) Str::uuid();
            }

            // Set default values if null
            if (is_null($model->hospital_stock)) {
                $model->hospital_stock = 0;
            }
            if (is_null($model->quantity)) {
                $model->quantity = 1;
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

    // Auto-slug
    public function setNameAttribute($value)
    {
        $this->attributes['name'] = $value;
        if (!isset($this->attributes['slug']) || empty($this->attributes['slug'])) {
            $this->attributes['slug'] = Str::slug($value);
        }
    }

    public function fill(array $attributes)
    {
        if (isset($attributes['name']) && empty($attributes['slug'])) {
            $attributes['slug'] = Str::slug($attributes['name']);
        }

        return parent::fill($attributes);
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    // Who can see medicines in table
    public function scopeVisibleTo($query, $user)
    {
        if (!$user) return $query->whereRaw('0=1');

        if ($user->hasRole('super_admin') || $user->can('view_any_medicines')) {
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
