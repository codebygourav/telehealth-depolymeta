<?php

namespace App\Traits;

use Filament\Resources\Resource;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

trait HasResourcePermissions
{
    /**
     * Get the resource slug for permission checking
     */
    public static function getPermissionSlug(): string
    {
        // Check if resource has a static $slug property
        if (property_exists(static::class, 'slug')) {
            $slug = static::$slug ?? null;
            if ($slug) {
                return $slug;
            }
        }

        // Try to get slug from resource method
        if (method_exists(static::class, 'getSlug')) {
            $slug = static::getSlug();
            if ($slug) {
                return $slug;
            }
        }

        // Fallback to class basename
        $className = class_basename(static::class);

        // Remove 'Resource' suffix if present
        $className = preg_replace('/Resource$/', '', $className);

        // Convert to kebab-case
        return strtolower(preg_replace('/([a-z])([A-Z])/', '$1-$2', $className));
    }

    /**
     * Check if a record belongs to the current user
     */
    protected static function isOwnRecord($record): bool
    {
        if (!$record || !Auth::check()) {
            return false;
        }

        $user = Auth::user();
        $userId = $user->id;
        $model = static::getModel();

        // Special handling for User model - a user's own record is themselves
        if ($model === \App\Models\User::class || $record instanceof \App\Models\User) {
            return $record->id === $userId;
        }

        // Check common ownership fields
        if (isset($record->user_id) && $record->user_id == $userId) {
            return true;
        }

        if (isset($record->created_by) && $record->created_by == $userId) {
            return true;
        }

        if ($record instanceof \App\Models\Appointment) {
            if ($record->relationLoaded('patient') || method_exists($record, 'patient')) {
                $patient = $record->patient;

                if ($patient && $patient->user_id === $userId) {
                    return true;
                }
            }

            if ($record->relationLoaded('doctor') || method_exists($record, 'doctor')) {
                $doctor = $record->doctor;

                if ($doctor && $doctor->user_id === $userId) {
                    return true;
                }
            }
        }

        // For models that might have different ownership patterns
        // Check if model has a method to determine ownership
        if (method_exists($record, 'isOwnedBy')) {
            return $record->isOwnedBy($user);
        }

        return false;
    }

    /**
     * Check if user has view_any permission (can see all records)
     */
    protected static function hasViewAnyPermission(): bool
    {
        $slug = static::getPermissionSlug();
        return check_permission("{$slug}.view_any");
    }

    /**
     * Check if user has view permission (can see own records)
     */
    protected static function hasViewPermission(): bool
    {
        $slug = static::getPermissionSlug();
        return check_permission("{$slug}.view");
    }

    /**
     * Check if user has manage_own permission
     */
    protected static function hasManageOwnPermission(): bool
    {
        $slug = static::getPermissionSlug();
        return check_permission("{$slug}.manage_own");
    }

    /**
     * Filter query based on permissions
     * If user has view_any, return query as is
     * If user has view or manage_own, filter to show only own records
     * Otherwise, return empty query
     */
    public static function filterQueryByOwnership(Builder $query): Builder
    {
        $user = Auth::user();
        if (!$user) {
            return $query->whereRaw('1 = 0'); // No user, no access
        }

        $slug = static::getPermissionSlug();
        $model = static::getModel();

        // If user has view_any permission, they can see all records
        if (check_permission("{$slug}.view_any")) {
            return $query;
        }

        // If user has view or manage_own permission, show only own records
        if (check_permission(["{$slug}.view", "{$slug}.manage_own"])) {
            $userId = $user->id;
            $table = (new $model())->getTable();

            // Special handling for User model - a user's own record is themselves
            if ($model === \App\Models\User::class) {
                return $query->where('id', $userId);
            }

            if ($model === \App\Models\Appointment::class) {
                if ($user->patient) {
                    return $query->whereHas('patient', fn (Builder $patientQuery) => $patientQuery->where('user_id', $userId));
                }

                if ($user->doctor) {
                    return $query->whereHas('doctor', fn (Builder $doctorQuery) => $doctorQuery->where('user_id', $userId));
                }
            }

            // Check which ownership field exists in the table
            $schema = \Illuminate\Support\Facades\Schema::getConnection()->getSchemaBuilder();
            if ($schema->hasColumn($table, 'user_id')) {
                return $query->where('user_id', $userId);
            }

            if ($schema->hasColumn($table, 'created_by')) {
                return $query->where('created_by', $userId);
            }

            // If model has a scope for ownership, use it
            if (method_exists($model, 'scopeOwnedBy')) {
                return $query->ownedBy($user);
            }
        }

        // No permission, return empty query
        return $query->whereRaw('1 = 0');
    }

    /**
     * Default canViewAny implementation
     * Returns true if user can view any records OR can view/manage own records
     */
    public static function canViewAny(): bool
    {
        $slug = static::getPermissionSlug();
        return check_permission(["{$slug}.view", "{$slug}.view_any", "{$slug}.manage_own"]);
    }

    /**
     * Default canCreate implementation
     */
    public static function canCreate(): bool
    {
        $slug = static::getPermissionSlug();
        return check_permission(["{$slug}.create", "{$slug}.manage_own"]);
    }

    /**
     * Default canEdit implementation
     * If user has update permission, can edit any
     * If user has manage_own permission, can only edit own records
     */
    public static function canEdit($record): bool
    {
        $slug = static::getPermissionSlug();
        $user = Auth::user();

        if (!$user) {
            return false;
        }

        // Check if user has update permission (can edit any)
        if (check_permission("{$slug}.update")) {
            return true;
        }

        // Check if user has manage_own permission
        if (check_permission("{$slug}.manage_own")) {
            // Can only edit own records
            return static::isOwnRecord($record);
        }

        return false;
    }

    /**
     * Default canDelete implementation
     * If user has delete_any permission, can delete any
     * If user has delete permission, can only delete own records
     * If user has manage_own permission, can only delete own records
     */
    public static function canDelete($record): bool
    {
        $slug = static::getPermissionSlug();
        $user = Auth::user();

        if (!$user) {
            return false;
        }

        // Check if user has delete_any permission (can delete any)
        if (check_permission("{$slug}.delete_any")) {
            return true;
        }

        // Check if user has delete permission
        if (check_permission("{$slug}.delete")) {
            // Can only delete own records
            return static::isOwnRecord($record);
        }

        // Check if user has manage_own permission
        if (check_permission("{$slug}.manage_own")) {
            // Can only delete own records
            return static::isOwnRecord($record);
        }

        return false;
    }

    /**
     * Default canView implementation (for view pages)
     * If user has view_any permission, can view any
     * If user has view or manage_own permission, can only view own records
     */
    public static function canView($record): bool
    {
        $slug = static::getPermissionSlug();
        $user = Auth::user();

        if (!$user) {
            return false;
        }

        // Check if user has view_any permission (can view any)
        if (check_permission("{$slug}.view_any")) {
            return true;
        }

        // Check if user has view or manage_own permission
        if (check_permission(["{$slug}.view", "{$slug}.manage_own"])) {
            // Can only view own records
            return static::isOwnRecord($record);
        }

        return false;
    }

    /**
     * Alias methods for compatibility with different naming conventions
     */
    public static function canViewRecord($record): bool
    {
        return static::canView($record);
    }

    public static function canEditRecord($record): bool
    {
        return static::canEdit($record);
    }

    public static function canDeleteRecord($record): bool
    {
        return static::canDelete($record);
    }
}
