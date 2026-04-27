<?php

namespace App\Models;

use Spatie\Permission\Models\Role as SpatieRole;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class Role extends SpatieRole
{
    protected $fillable = ['name', 'guard_name'];
    // In your Role model
    protected $attributes = [
        'name' => 'unnamed_role',
    ];

    /**
     * Scope: Only roles that the user has any permission for
     */
    public function scopeVisibleTo(Builder $query, $user = null): Builder
    {
        $user = $user ?? Auth::user();
        if ($user->hasRole('super_admin')) {
            return $query;
        }

        $userPermissions = $user->getAllPermissions()->pluck('id')->toArray();

        return $query->whereHas('permissions', function ($q) use ($userPermissions) {
            $q->whereIn('id', $userPermissions);
        });
    }

    /**
     * Check if the role is a system role that should not be deleted or modified
     */
    public function isSystemRole(): bool
    {
        return in_array($this->name, ['super_admin', 'admin', 'doctor', 'patient']);
    }

    protected static function boot()
    {
        parent::boot();

        static::deleting(function ($role) {
            if ($role->isSystemRole()) {
                throw new \Exception("System roles cannot be deleted.");
            }
        });
    }
}
