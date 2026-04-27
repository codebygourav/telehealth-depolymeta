<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Auth;

class Setting extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'group',
        'key',
        'value',
        'type',
        'description',
        'is_public',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected $casts = [
        'is_public' => 'boolean',
    ];

    /**
     * Get a setting value by group and key
     */
    public static function getValue(string $group, string $key, mixed $default = null): mixed
    {
        $setting = Cache::remember(
            "setting.{$group}.{$key}",
            now()->addHours(24),
            fn() => static::where('group', $group)->where('key', $key)->first()
        );

        if (!$setting) {
            return $default;
        }

        return static::castValue($setting->value, $setting->type);
    }

    /**
     * Set a setting value
     */
    public static function setValue(string $group, string $key, mixed $value, string $type = 'string', ?string $description = null, bool $isPublic = false): static
    {
        $setting = static::updateOrCreate(
            ['group' => $group, 'key' => $key],
            [
                'value' => is_array($value) || is_object($value) ? json_encode($value) : $value,
                'type' => $type,
                'description' => $description,
                'is_public' => $isPublic,
            ]
        );

        Cache::forget("setting.{$group}.{$key}");
        Cache::forget("settings.{$group}");

        return $setting;
    }

    /**
     * Get all settings for a group
     */
    public static function getGroup(string $group): array
    {
        return Cache::remember(
            "settings.{$group}",
            now()->addHours(24),
            function () use ($group) {
                return static::where('group', $group)
                    ->get()
                    ->mapWithKeys(function ($setting) {
                        return [$setting->key => static::castValue($setting->value, $setting->type)];
                    })
                    ->toArray();
            }
        );
    }

    /**
     * Get all public settings
     */
    public static function getPublicSettings(): array
    {
        return Cache::remember(
            'settings.public',
            now()->addHours(24),
            function () {
                return static::where('is_public', true)
                    ->get()
                    ->mapWithKeys(function ($setting) {
                        return ["{$setting->group}.{$setting->key}" => static::castValue($setting->value, $setting->type)];
                    })
                    ->toArray();
            }
        );
    }

    /**
     * Cast value based on type
     */
    protected static function castValue(mixed $value, string $type): mixed
    {
        return match ($type) {
            'boolean' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            'integer' => (int) $value,
            'float' => (float) $value,
            'json', 'array' => is_string($value) ? json_decode($value, true) : $value,
            default => $value,
        };
    }

    /**
     * Clear all settings cache
     */
    public static function clearCache(): void
    {
        $groups = static::distinct('group')->pluck('group');

        foreach ($groups as $group) {
            Cache::forget("settings.{$group}");

            $keys = static::where('group', $group)->pluck('key');
            foreach ($keys as $key) {
                Cache::forget("setting.{$group}.{$key}");
            }
        }

        Cache::forget('settings.public');
    }

    /**
     * Scope to filter by group
     */
    public function scopeGroup($query, string $group)
    {
        return $query->where('group', $group);
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
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
