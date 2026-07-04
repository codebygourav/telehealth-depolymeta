<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class DisplayScreen extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'is_active',
        'settings',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'settings' => 'array',
    ];

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    protected static function booted(): void
    {
        static::creating(function (self $screen): void {
            $screen->slug = $screen->slug ?: Str::slug($screen->name);

            if (Auth::check()) {
                $screen->created_by = Auth::id();
                $screen->updated_by = Auth::id();
            }
        });

        static::updating(function (self $screen): void {
            if (blank($screen->slug)) {
                $screen->slug = Str::slug($screen->name);
            }

            if (Auth::check()) {
                $screen->updated_by = Auth::id();
            }
        });

        static::deleting(function (self $screen): void {
            if (Auth::check() && ! $screen->isForceDeleting()) {
                $screen->deleted_by = Auth::id();
                $screen->save();
            }
        });
    }
}
