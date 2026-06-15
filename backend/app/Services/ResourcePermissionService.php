<?php

namespace App\Services;

use Filament\Resources\Resource;
use Filament\Pages\Page;
use Filament\Facades\Filament;

class ResourcePermissionService
{
    /**
     * Get available actions for a resource based on its pages, table actions, and permission methods
     */
    public static function getAvailableActions(string $resourceClass): array
    {
        if (!is_subclass_of($resourceClass, Resource::class)) {
            return [];
        }

        $actions = [];
        $pages = [];

        // Check if resource has getPages method
        if (method_exists($resourceClass, 'getPages')) {
            $pages = $resourceClass::getPages();
        }

        // Check for index page (view_any) - always available if resource exists
        if (isset($pages['index'])) {
            $actions[] = 'view_any';
        }

        // Check for view page
        if (isset($pages['view'])) {
            $actions[] = 'view';
        }

        // Check for create - must have page/action AND canCreate must not return false
        if (static::isCreateAvailable($resourceClass, $pages)) {
            $actions[] = 'create';
        }

        // Check for edit - must have page/action AND canEdit must not return false
        if (static::isEditAvailable($resourceClass, $pages)) {
            $actions[] = 'update';
            $actions[] = 'manage_own'; // manage_own is typically used with update
        }

        // Check for delete - must have action AND canDelete must not return false
        if (static::isDeleteAvailable($resourceClass)) {
            $actions[] = 'delete';
            $actions[] = 'delete_any';
        }

        // Resource specific actions
        if (str_contains($resourceClass, 'UserResource')) {
            $actions[] = 'assign_role';
        }

        // Remove duplicates and return
        return array_unique($actions);
    }

    /**
     * Check if create is actually available (has page/action AND canCreate doesn't return false)
     */
    protected static function isCreateAvailable(string $resourceClass, array $pages): bool
    {
        // First check if there's a create page or create action
        $hasCreatePage = isset($pages['create']);
        $hasCreateAction = static::hasCreateAction($resourceClass);

        if (!$hasCreatePage && !$hasCreateAction) {
            return false;
        }

        // Now check if canCreate method exists and what it returns
        if (method_exists($resourceClass, 'canCreate')) {
            // Check if canCreate is overridden to return false
            if (static::methodReturnsFalse($resourceClass, 'canCreate')) {
                return false;
            }
        }

        return true;
    }

    /**
     * Check if edit is actually available (has page/action AND canEdit doesn't return false)
     */
    protected static function isEditAvailable(string $resourceClass, array $pages): bool
    {
        // First check if there's an edit page or edit action
        $hasEditPage = isset($pages['edit']);
        $hasEditAction = static::hasEditAction($resourceClass);

        if (!$hasEditPage && !$hasEditAction) {
            return false;
        }

        // Now check if canEdit method exists and what it returns
        if (method_exists($resourceClass, 'canEdit')) {
            // Check if canEdit is overridden to return false
            if (static::methodReturnsFalse($resourceClass, 'canEdit')) {
                return false;
            }
        }

        return true;
    }

    /**
     * Check if delete is actually available (has action AND canDelete doesn't return false)
     */
    protected static function isDeleteAvailable(string $resourceClass): bool
    {
        // First check if there's a delete action
        if (!static::hasDeleteAction($resourceClass)) {
            return false;
        }

        // Now check if canDelete method exists and what it returns
        if (method_exists($resourceClass, 'canDelete')) {
            // Check if canDelete is overridden to return false
            if (static::methodReturnsFalse($resourceClass, 'canDelete')) {
                return false;
            }
        }

        return true;
    }

    /**
     * Check if a method is overridden to return false
     * This detects custom implementations that disable functionality
     */
    protected static function methodReturnsFalse(string $resourceClass, string $methodName): bool
    {
        try {
            $reflection = new \ReflectionClass($resourceClass);

            // Check if method exists
            if (!$reflection->hasMethod($methodName)) {
                return false;
            }

            $method = $reflection->getMethod($methodName);

            // Get the file and line numbers
            $filename = $method->getFileName();
            $startLine = $method->getStartLine();
            $endLine = $method->getEndLine();

            // Read the method source
            $source = file_get_contents($filename);
            $lines = explode("\n", $source);
            $methodSource = implode("\n", array_slice($lines, $startLine - 1, $endLine - $startLine + 1));

            // Remove comments (both /* */ and //)
            $cleanSource = preg_replace('/\/\*.*?\*\//s', '', $methodSource);
            $cleanSource = preg_replace('/\/\/.*$/m', '', $cleanSource);

            // Check for simple "return false;" pattern
            // This matches methods that directly return false without conditions
            // Pattern: method body contains "return false;" and it's likely the main/only return
            if (preg_match('/return\s+false\s*;/i', $cleanSource)) {
                // Extract just the method body (between { and })
                if (preg_match('/\{([^}]*)\}/s', $cleanSource, $matches)) {
                    $body = $matches[1];

                    // Remove all whitespace and newlines for easier matching
                    $bodyClean = preg_replace('/\s+/', ' ', trim($body));

                    // Check if body is essentially just "return false;" or has minimal code before it
                    // Patterns like: "return false;" or "// comment return false;"
                    if (preg_match('/^(?:\/\/[^;]*\s+)?return\s+false\s*;?\s*$/', $bodyClean)) {
                        return true;
                    }

                    // Check if the first significant statement is "return false;"
                    // Remove comments and check
                    $bodyNoComments = preg_replace('/\/\/[^\n]*/', '', $body);
                    $bodyNoComments = trim($bodyNoComments);
                    if (preg_match('/^\s*return\s+false\s*;?\s*$/', $bodyNoComments)) {
                        return true;
                    }
                }
            }
        } catch (\Exception $e) {
            // If we can't determine, assume it doesn't return false
            return false;
        }

        return false;
    }

    /**
     * Check if resource has create action (via slideOver in list page)
     */
    protected static function hasCreateAction(string $resourceClass): bool
    {
        try {
            $pages = $resourceClass::getPages();
            if (!isset($pages['index'])) {
                return false;
            }

            $listPageClass = $pages['index']->getPage();
            if (class_exists($listPageClass)) {
                // Check if list page has CreateAction in header actions
                $reflection = new \ReflectionClass($listPageClass);
                if ($reflection->hasMethod('getHeaderActions')) {
                    // We can't easily check this without instantiating, so we'll check the source
                    $source = file_get_contents($reflection->getFileName());
                    if (strpos($source, 'CreateAction') !== false || strpos($source, 'create') !== false) {
                        return true;
                    }
                }
            }
        } catch (\Exception $e) {
            // If we can't determine, assume false
        }

        return false;
    }

    /**
     * Check if resource has edit action (via slideOver in table)
     */
    protected static function hasEditAction(string $resourceClass): bool
    {
        try {
            // Try to get table class from resource
            $reflection = new \ReflectionClass($resourceClass);
            $source = file_get_contents($reflection->getFileName());

            // Check if table method exists and might have EditAction
            if (
                preg_match('/table\([^)]*\)\s*->\s*recordActions\(/i', $source) ||
                preg_match('/EditAction/i', $source)
            ) {
                return true;
            }

            // Also check table class directly
            $resourceDir = dirname($reflection->getFileName());
            $tableFile = $resourceDir . '/Tables/' . class_basename($resourceClass) . 'Table.php';
            if (file_exists($tableFile)) {
                $tableSource = file_get_contents($tableFile);
                if (strpos($tableSource, 'EditAction') !== false) {
                    return true;
                }
            }
        } catch (\Exception $e) {
            // If we can't determine, assume false
        }

        return false;
    }

    /**
     * Check if resource has delete action
     */
    protected static function hasDeleteAction(string $resourceClass): bool
    {
        try {
            $reflection = new \ReflectionClass($resourceClass);
            $source = file_get_contents($reflection->getFileName());

            // Check if table method exists and might have DeleteAction
            if (
                preg_match('/DeleteAction/i', $source) ||
                preg_match('/DeleteBulkAction/i', $source)
            ) {
                return true;
            }

            // Also check table class directly
            $resourceDir = dirname($reflection->getFileName());
            $tableFile = $resourceDir . '/Tables/' . class_basename($resourceClass) . 'Table.php';
            if (file_exists($tableFile)) {
                $tableSource = file_get_contents($tableFile);
                if (
                    strpos($tableSource, 'DeleteAction') !== false ||
                    strpos($tableSource, 'DeleteBulkAction') !== false
                ) {
                    return true;
                }
            }
        } catch (\Exception $e) {
            // If we can't determine, assume false
        }

        return false;
    }

    /**
     * Get available actions for a page
     */
    public static function getAvailableActionsForPage(string $pageClass): array
    {
        if (!is_subclass_of($pageClass, Page::class)) {
            return [];
        }

        // For pages, we typically only have view/view_any
        // You can customize this based on your page structure
        return ['view', 'view_any'];
    }

    /**
     * Get all resources with their available actions
     */
    public static function getResourcesWithActions(): array
    {
        $resources = Filament::getResources();
        $result = [];

        foreach ($resources as $resourceClass) {
            try {
                $slug = static::getResourceSlug($resourceClass);
                $actions = static::getAvailableActions($resourceClass);

                // Always include resources, even if they have no actions yet
                // This ensures all resources appear in the role-permission page
                if (empty($actions)) {
                    // If no actions detected, at least add view_any if resource has index page
                    $pages = [];
                    if (method_exists($resourceClass, 'getPages')) {
                        $pages = $resourceClass::getPages();
                    }
                    if (isset($pages['index'])) {
                        $actions = ['view_any'];
                    }
                }

                if (!empty($actions)) {
                    $result[$slug] = [
                        'class' => $resourceClass,
                        'actions' => $actions,
                        'type' => 'resource',
                        'group' => static::getModuleGroup($resourceClass),
                        'label' => static::getModuleLabel($resourceClass),
                    ];
                }
            } catch (\Exception $e) {
                // Skip resources that cause errors
                continue;
            }
        }

        return $result;
    }

    /**
     * Get all pages with their available actions
     */
    public static function getPagesWithActions(): array
    {
        $pages = Filament::getPages();
        $result = [];

        foreach ($pages as $pageClass) {
            // Skip RolePermissionMatrix itself
            if ($pageClass === \App\Filament\Pages\RolePermissionMatrix::class) {
                continue;
            }

            $slug = static::getPageSlug($pageClass);
            $actions = static::getAvailableActionsForPage($pageClass);

            if (!empty($actions)) {
                $result[$slug] = [
                    'class' => $pageClass,
                    'actions' => $actions,
                    'type' => 'page',
                    'group' => static::getModuleGroup($pageClass),
                    'label' => static::getPageLabel($pageClass),
                ];
            }
        }

        return $result;
    }

    /**
     * Get all modules (resources + pages) with their available actions
     */
    public static function getAllModulesWithActions(): array
    {
        $resources = static::getResourcesWithActions();
        $pages = static::getPagesWithActions();

        $allModules = array_merge($resources, $pages);

        // Add permissions from database that might not be detected
        try {
            $dbPermissions = \App\Models\Permission::all();
            foreach ($dbPermissions as $permission) {
                if (strpos($permission->name, '.') !== false) {
                    [$module, $action] = explode('.', $permission->name, 2);

                    if (isset($allModules[$module])) {
                        if (!in_array($action, $allModules[$module]['actions'])) {
                            $allModules[$module]['actions'][] = $action;
                        }
                    } else {
                        // Custom module from DB
                        $allModules[$module] = [
                            'class' => null,
                            'actions' => [$action],
                            'type' => 'custom',
                            'group' => 'Custom',
                            'label' => \Illuminate\Support\Str::headline($module),
                        ];
                    }
                }
            }
        } catch (\Exception $e) {
            // Log or ignore
        }

        return $allModules;
    }

    /**
     * Get resource slug
     */
    protected static function getResourceSlug(string $resourceClass): string
    {
        if (property_exists($resourceClass, 'slug')) {
            $slug = $resourceClass::$slug ?? null;
            if ($slug) {
                return $slug;
            }
        }

        if (method_exists($resourceClass, 'getSlug')) {
            $slug = $resourceClass::getSlug();
            if ($slug) {
                return $slug;
            }
        }

        $className = class_basename($resourceClass);
        $className = preg_replace('/Resource$/', '', $className);
        return strtolower(preg_replace('/([a-z])([A-Z])/', '$1-$2', $className));
    }

    /**
     * Get page slug
     */
    protected static function getPageSlug(string $pageClass): string
    {
        if (property_exists($pageClass, 'slug')) {
            $slug = $pageClass::$slug ?? null;
            if ($slug) {
                return $slug;
            }
        }

        $className = class_basename($pageClass);
        return strtolower(preg_replace('/([a-z])([A-Z])/', '$1-$2', $className));
    }

    /**
     * Get permission name for a module and action
     */
    public static function getPermissionName(string $module, string $action): string
    {
        return "{$module}.{$action}";
    }

    /**
     * Get the navigation group for a class
     */
    public static function getModuleGroup(string $class): string
    {
        try {
            if (property_exists($class, 'navigationGroup')) {
                $reflection = new \ReflectionClass($class);
                $prop = $reflection->getProperty('navigationGroup');
                $prop->setAccessible(true);
                $value = $prop->getValue();
                
                if ($value instanceof \BackedEnum) {
                    return $value->value;
                }
                if ($value instanceof \UnitEnum) {
                    return $value->name;
                }
                return $value ?? 'Other';
            }
            
            if (method_exists($class, 'getNavigationGroup')) {
                return (string) $class::getNavigationGroup() ?? 'Other';
            }
        } catch (\Exception $e) {}

        return 'Other';
    }

    /**
     * Get the label for a resource
     */
    public static function getModuleLabel(string $resourceClass): string
    {
        if (property_exists($resourceClass, 'navigationLabel')) {
            $reflection = new \ReflectionClass($resourceClass);
            $prop = $reflection->getProperty('navigationLabel');
            $prop->setAccessible(true);
            $value = $prop->getValue();
            if ($value) return $value;
        }

        if (method_exists($resourceClass, 'getNavigationLabel')) {
            return $resourceClass::getNavigationLabel();
        }

        return \Illuminate\Support\Str::headline(class_basename($resourceClass));
    }

    /**
     * Get the label for a page
     */
    public static function getPageLabel(string $pageClass): string
    {
        if (property_exists($pageClass, 'title')) {
            $reflection = new \ReflectionClass($pageClass);
            $prop = $reflection->getProperty('title');
            $prop->setAccessible(true);
            $value = $prop->getValue();
            if ($value) return $value;
        }

        return \Illuminate\Support\Str::headline(class_basename($pageClass));
    }
}
