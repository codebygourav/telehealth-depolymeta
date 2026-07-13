<?php
// app/Helpers/PermissionHelper.php

use Illuminate\Support\Facades\Auth;

if (!function_exists('check_permission')) {
    /**
     * Check if the currently authenticated user has **any** of the given permissions
     * Super-admin bypass is included
     */
    function check_permission(array|string $permissions): bool
    {
        $user = Auth::user();
        if (!$user) return false;

        // Super-admin bypass
        if ($user->hasRole('super_admin')) return true;

        if (is_string($permissions)) {
            $permissions = [$permissions];
        }

        foreach ($permissions as $permission) {
            // Direct check first
            if ($user->can($permission)) return true;

            if (
                $user->hasRole('patient') &&
                in_array($permission, [
                    'patients.view',
                    'patients.manage_own',
                ], true)
            ) {
                return true;
            }

            // Backwards-compatible conversion for legacy snake_case permission names
            // e.g. "view_medicines" -> "medicines.view"
            // e.g. "view_any_medicine_categories" -> "medicine-categories.view_any"
            // e.g. "edit_medicines" -> "medicines.update"
            $converted = null;

            // Normalize string
            $p = (string) $permission;

            // Handle manage_* legacy mapping to manage_own for that module
            if (str_starts_with($p, 'manage_')) {
                $resource = substr($p, strlen('manage_'));
                $module = str_replace('_', '-', $resource);
                $converted = "{$module}.manage_own";
            } else {
                // Try to split action and resource parts
                // Matches patterns like action[_any]_resource_rest
                $parts = explode('_', $p);
                if (count($parts) >= 2) {
                    // If starts with view_any or delete_any
                    if (in_array($parts[0] . '_' . ($parts[1] ?? ''), ['view_any', 'delete_any'])) {
                        $action = $parts[0] . '_' . $parts[1];
                        $resourceParts = array_slice($parts, 2);
                    } else {
                        $action = $parts[0];
                        $resourceParts = array_slice($parts, 1);
                    }

                    // Map legacy action names to new prefixes
                    if ($action === 'edit') $action = 'update';
                    if ($action === 'edit_any') $action = 'update';

                    // Build module slug from resourceParts
                    $resource = implode('_', $resourceParts);
                    if (!empty($resource)) {
                        $module = str_replace('_', '-', $resource);
                        $converted = "{$module}.{$action}";
                    }
                }
            }

            if ($converted && $user->can($converted)) return true;
        }

        return false;
    }
}
