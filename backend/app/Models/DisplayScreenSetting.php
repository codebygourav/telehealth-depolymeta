<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class DisplayScreenSetting extends Model
{
    use SoftDeletes;

    protected $table = 'display_screen_settings';

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

    public static function getValue(string $group, string $key, mixed $default = null): mixed
    {
        $setting = Cache::remember(
            "display_screen_setting.{$group}.{$key}",
            now()->addHours(24),
            fn () => static::where('group', $group)->where('key', $key)->first()
        );

        if (! $setting) {
            return $default;
        }

        return static::castValue($setting->value, $setting->type);
    }

    public static function setValue(
        string $group,
        string $key,
        mixed $value,
        string $type = 'string',
        ?string $description = null,
        bool $isPublic = false
    ): static {
        $setting = static::updateOrCreate(
            ['group' => $group, 'key' => $key],
            [
                'value' => is_array($value) || is_object($value) ? json_encode($value) : $value,
                'type' => $type,
                'description' => $description,
                'is_public' => $isPublic,
            ]
        );

        Cache::forget("display_screen_setting.{$group}.{$key}");
        Cache::forget("display_screen_settings.{$group}");

        return $setting;
    }

    public static function getGroup(string $group): array
    {
        return Cache::remember(
            "display_screen_settings.{$group}",
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

    public static function clearCache(): void
    {
        $groups = static::distinct('group')->pluck('group');

        foreach ($groups as $group) {
            Cache::forget("display_screen_settings.{$group}");

            $keys = static::where('group', $group)->pluck('key');
            foreach ($keys as $key) {
                Cache::forget("display_screen_setting.{$group}.{$key}");
            }
        }
    }

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
}
