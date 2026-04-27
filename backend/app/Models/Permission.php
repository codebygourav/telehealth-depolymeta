<?php

namespace App\Models;

use Spatie\Permission\Models\Permission as SpatiePermission;
use Illuminate\Support\Str;

class Permission extends SpatiePermission
{
    protected $fillable = ['name', 'guard_name', 'group', 'description'];

    /**
     * Get the module name (group) from the permission name
     * e.g. "appointments.view" -> "appointments"
     */
    public function getModuleAttribute(): string
    {
        if (str_contains($this->name, '.')) {
            return explode('.', $this->name)[0];
        }

        return 'system';
    }

    /**
     * Get the action name from the permission name
     * e.g. "appointments.view" -> "view"
     */
    public function getActionAttribute(): string
    {
        if (str_contains($this->name, '.')) {
            return explode('.', $this->name)[1];
        }

        return $this->name;
    }

    /**
     * Get a human-friendly label for the permission
     */
    public function getLabelAttribute(): string
    {
        return Str::headline($this->action);
    }
}
