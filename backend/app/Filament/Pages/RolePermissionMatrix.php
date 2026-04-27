<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Spatie\Permission\Models\{Role, Permission};
use Filament\Support\Icons\Heroicon;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\TextInput;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Cache;
use App\Traits\HasCustomSidebar;

class RolePermissionMatrix extends Page
{
    use HasCustomSidebar;

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedShieldCheck;
    protected static ?string $navigationLabel = 'Role & Permission';
    protected static ?string $title = 'Role & Permission';
    protected static ?string $slug = 'role-permission';
    protected static string|\UnitEnum|null $navigationGroup = 'User Management';
    protected static ?int $navigationSort = 31;

    protected string $view = 'filament.pages.role-permission-matrix';

    public $roles = [];
    public $modules = [];
    public $permissions = [];

    /**
     * Filament-style default permission prefixes
     */
    public static function getPermissionPrefixes(): array
    {
        return [
            'view',
            'view_any',
            'manage_own',
            'create',
            'update',
            'delete',
            'delete_any',
        ];
    }

    public function mount()
    {
        // Authorize page access by permissions. If user lacks view permissions, abort.
        $module = static::$slug ?? strtolower(class_basename(static::class));
        if (! check_permission(["{$module}.view", "{$module}.view_any"])) {
            abort(403);
        }

        $this->loadData();
    }

    /**
     * Load roles, permissions, and modules data
     * This is called after any permission changes to refresh the UI
     */
    public function loadData()
    {
        $allPermissionsModels = \App\Models\Permission::all();
        $this->permissions = $allPermissionsModels;
        
        $this->roles = Role::with('permissions')->get();
        $this->modules = $this->groupPermissionsByModule();

        // Ensure Super Admin roles always have all permissions in reality
        $allPermissionNames = $allPermissionsModels->pluck('name')->toArray();
        
        $protectedRoles = Role::whereIn('name', ['super_admin', 'super-admin', 'Super Admin'])->get();
        foreach ($protectedRoles as $role) {
            $role->syncPermissions($allPermissionNames);
        }
    }

    /**
     * Check if a role is a protected Super Admin role
     */
    protected function isProtectedRole($role): bool
    {
        $name = is_string($role) ? $role : ($role->name ?? $role['name'] ?? '');
        return in_array(strtolower($name), ['super_admin', 'super-admin','super admin']);
    }

    public static function shouldRegisterNavigation(): bool
    {
        $module = static::$slug ?? strtolower(class_basename(static::class));
        return check_permission(["{$module}.view", "{$module}.view_any"]);
    }

    /**
     * Toggle all permissions for a given module for a role.
     * If the role already has all module permissions, revoke them; otherwise grant missing ones.
     * This ensures permissions are properly added/removed from database.
     */
    public function toggleModulePermissions($roleId, $module)
    {
        // Authorize update action
        $matrixModule = static::$slug ?? strtolower(class_basename(static::class));
        if (! check_permission(["{$matrixModule}.update", "{$matrixModule}.manage"])) {
            \Filament\Notifications\Notification::make()
                ->title('Unauthorized')
                ->body('You do not have permission to modify roles or permissions.')
                ->danger()
                ->send();
            return;
        }

        try {
            $role = Role::findOrFail($roleId);

            if ($this->isProtectedRole($role)) {
                \Filament\Notifications\Notification::make()
                    ->title('Access Denied')
                    ->body('Cannot modify permissions for Super Admin roles.')
                    ->warning()
                    ->send();
                return;
            }

            // Get actual available actions for this module
            $allModules = \App\Services\ResourcePermissionService::getAllModulesWithActions();
            $moduleData = $allModules[$module] ?? null;

            if (!$moduleData) {
                return;
            }

            $availableActions = $moduleData['actions'] ?? [];

            // Build permission names for this module's available actions
            $permissionNames = [];
            foreach ($availableActions as $action) {
                $permissionName = "{$module}.{$action}";
                // Ensure permission exists in database
                Permission::firstOrCreate(
                    ['name' => $permissionName, 'guard_name' => 'web'],
                    ['name' => $permissionName, 'guard_name' => 'web']
                );
                $permissionNames[] = $permissionName;
            }

            // Check if role has all module permissions
            $hasAll = true;
            foreach ($permissionNames as $p) {
                if (!$role->hasPermissionTo($p)) {
                    $hasAll = false;
                    break;
                }
            }

            if ($hasAll) {
                // Remove ALL permissions for this module (removes from role_has_permissions pivot table)
                foreach ($permissionNames as $p) {
                    $role->revokePermissionTo($p);
                }
            } else {
                // Grant ALL permissions for this module (adds to role_has_permissions pivot table)
                foreach ($permissionNames as $p) {
                    $role->givePermissionTo($p);
                }
            }

            // Clear Spatie Permission cache to ensure changes are reflected immediately
            app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

            // Refresh roles to get updated permissions
            $this->loadData();

            \Filament\Notifications\Notification::make()
                ->title('Success')
                ->body($hasAll ? 'All permissions removed for this module' : 'All permissions granted for this module')
                ->success()
                ->send();
        } catch (\Exception $e) {
            \Filament\Notifications\Notification::make()
                ->title('Error')
                ->body('Failed to toggle module permissions: ' . $e->getMessage())
                ->danger()
                ->send();
        }
    }

    /**
     * Toggle all permissions across all modules for a role.
     * If the role has all permissions, revoke all; otherwise grant all.
     * This ensures permissions are properly added/removed from database.
     */
    public function toggleAllPermissions($roleId)
    {
        // Authorize update action
        $matrixModule = static::$slug ?? strtolower(class_basename(static::class));
        if (! check_permission(["{$matrixModule}.update", "{$matrixModule}.manage"])) {
            \Filament\Notifications\Notification::make()
                ->title('Unauthorized')
                ->body('You do not have permission to modify roles or permissions.')
                ->danger()
                ->send();
            return;
        }

        try {
            $role = Role::findOrFail($roleId);

            if ($this->isProtectedRole($role)) {
                \Filament\Notifications\Notification::make()
                    ->title('Access Denied')
                    ->body('Cannot modify permissions for Super Admin roles.')
                    ->warning()
                    ->send();
                return;
            }
            $allPermissions = Permission::all();

            $hasAll = true;
            foreach ($allPermissions as $p) {
                if (!$role->hasPermissionTo($p->name)) {
                    $hasAll = false;
                    break;
                }
            }

            if ($hasAll) {
                // Remove ALL permissions from role (removes all from role_has_permissions pivot table)
                $role->syncPermissions([]);
            } else {
                // Grant ALL permissions to role (adds all to role_has_permissions pivot table)
                $role->syncPermissions($allPermissions);
            }

            // Clear Spatie Permission cache to ensure changes are reflected immediately
            app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

            // Refresh roles to get updated permissions
            $this->loadData();

            \Filament\Notifications\Notification::make()
                ->title('Success')
                ->body($hasAll ? 'All permissions removed for this role' : 'All permissions granted for this role')
                ->success()
                ->send();
        } catch (\Exception $e) {
            \Filament\Notifications\Notification::make()
                ->title('Error')
                ->body('Failed to toggle all permissions: ' . $e->getMessage())
                ->danger()
                ->send();
        }
    }

    private function groupPermissionsByModule()
    {
        $modules = [];

        // Use ResourcePermissionService to get modules with their available actions
        $allModules = \App\Services\ResourcePermissionService::getAllModulesWithActions();

        foreach ($allModules as $moduleSlug => $moduleData) {
            $actions = $moduleData['actions'] ?? [];
            $group = $moduleData['group'] ?? 'Other';
            $label = $moduleData['label'] ?? \Illuminate\Support\Str::headline($moduleSlug);

            foreach ($actions as $action) {
                // Ensure permission exists in database
                $permissionName = \App\Services\ResourcePermissionService::getPermissionName($moduleSlug, $action);

                \App\Models\Permission::firstOrCreate([
                    'name' => $permissionName,
                    'guard_name' => 'web',
                ]);

                $modules[$group][$moduleSlug]['label'] = $label;
                $modules[$group][$moduleSlug]['actions'][] = $action;
            }
        }

        return $modules;
    }

    /**
     * Sync all permissions from resources and pages to the database
     */
    public function syncAllPermissions()
    {
        try {
            $allModules = \App\Services\ResourcePermissionService::getAllModulesWithActions();
            $count = 0;

            foreach ($allModules as $moduleSlug => $moduleData) {
                $actions = $moduleData['actions'] ?? [];
                foreach ($actions as $action) {
                    $permissionName = \App\Services\ResourcePermissionService::getPermissionName($moduleSlug, $action);
                    
                    \App\Models\Permission::firstOrCreate(
                        ['name' => $permissionName, 'guard_name' => 'web'],
                        ['name' => $permissionName, 'guard_name' => 'web']
                    );
                    $count++;
                }
            }

            $this->loadData();

            \Filament\Notifications\Notification::make()
                ->title('Permissions Synced')
                ->body("Successfully synced {$count} permissions from resources and pages.")
                ->success()
                ->send();
        } catch (\Exception $e) {
            \Filament\Notifications\Notification::make()
                ->title('Sync Failed')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    /**
     * Toggle permission on/off for a role
     * When unchecked, removes permission from database
     * When checked, adds permission to database
     */
    public function togglePermission($roleId, $permissionName)
    {
        // Authorize update action
        $module = static::$slug ?? strtolower(class_basename(static::class));
        if (! check_permission(["{$module}.update", "{$module}.manage"])) {
            \Filament\Notifications\Notification::make()
                ->title('Unauthorized')
                ->body('You do not have permission to modify roles or permissions.')
                ->danger()
                ->send();
            return;
        }

        try {
            $role = Role::findOrFail($roleId);

            if ($this->isProtectedRole($role)) {
                \Filament\Notifications\Notification::make()
                    ->title('Access Denied')
                    ->body('Cannot modify permissions for Super Admin roles.')
                    ->warning()
                    ->send();
                return;
            }

            // Ensure permission exists in database
            $permission = Permission::firstOrCreate(
                ['name' => $permissionName, 'guard_name' => 'web'],
                ['name' => $permissionName, 'guard_name' => 'web']
            );

            $hadPermission = $role->hasPermissionTo($permissionName);

            if ($hadPermission) {
                // Remove permission from role (removes from role_has_permissions pivot table)
                $role->revokePermissionTo($permissionName);
            } else {
                // Add permission to role (adds to role_has_permissions pivot table)
                $role->givePermissionTo($permissionName);
            }

            // Clear Spatie Permission cache to ensure changes are reflected immediately
            app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

            // Refresh roles to get updated permissions
            $this->loadData();
        } catch (\Exception $e) {
            \Filament\Notifications\Notification::make()
                ->title('Error')
                ->body('Failed to toggle permission: ' . $e->getMessage())
                ->danger()
                ->send();
        }
    }

    /**
     * Delete a role
     */
    public function deleteRole($roleId)
    {
        try {
            $role = Role::findOrFail($roleId);
            
            // Protect core roles
            if (in_array(strtolower($role->name), ['super_admin', 'super-admin', 'admin'])) {
                throw new \Exception("Cannot delete protected role: {$role->name}");
            }

            $role->delete();

            // Refresh roles
            $this->loadData();

            \Filament\Notifications\Notification::make()
                ->title('Role Deleted')
                ->body("Successfully deleted role: {$role->name}")
                ->success()
                ->send();
        } catch (\Exception $e) {
            \Filament\Notifications\Notification::make()
                ->title('Delete Failed')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    /**
     * Header top-right actions
     */
    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\Action::make('syncPermissions')
                ->label('Sync Permissions')
                ->color('gray')
                ->icon('heroicon-o-arrow-path')
                ->action(fn() => $this->syncAllPermissions()),

            \Filament\Actions\Action::make('addPermission')
                ->label('Add Permission')
                ->color('primary')
                ->icon('heroicon-o-plus-circle')
                ->slideOver()
                ->form([
                    \Filament\Forms\Components\Select::make('module')
                        ->label('Module')
                        ->options(function() {
                            $modules = [];
                            $allModules = \App\Services\ResourcePermissionService::getAllModulesWithActions();
                            foreach ($allModules as $slug => $data) {
                                $modules[$slug] = $data['label'] ?? \Illuminate\Support\Str::headline($slug);
                            }
                            return $modules;
                        })
                        ->searchable()
                        ->required()
                        ->placeholder('Select or type module slug'),
                    TextInput::make('action')
                        ->label('Action')
                        ->placeholder('e.g., create, update, delete, view')
                        ->required(),
                    TextInput::make('permission_label')
                        ->label('Permission Label')
                        ->placeholder('e.g., transport_create')
                        ->helperText('Use lowercase with underscores (e.g., transport_create)')
                        ->required(),
                ])
                ->visible(fn() => check_permission(['role-permission.update', 'role-permission.manage']))
                ->action(function ($data) {
                    $module = $data['module'];
                    $action = $data['action'];
                    $permissionName = $module . '.' . $action;

                    // Load current data to check existence
                    $allModules = \App\Services\ResourcePermissionService::getAllModulesWithActions();
                    
                    // Check if the permission (combination of module and action) already exists
                    $alreadyExists = false;
                    if (isset($allModules[$module]) && in_array($action, $allModules[$module]['actions'])) {
                        $alreadyExists = true;
                    } else {
                        // Double check database for custom permissions not yet in the list
                        $alreadyExists = \App\Models\Permission::where('name', $permissionName)->exists();
                    }

                    if ($alreadyExists) {
                        \Filament\Notifications\Notification::make()
                            ->title('Already Added')
                            ->body("The module '{$module}' already has the '{$action}' permission.")
                            ->warning()
                            ->send();
                        return;
                    }
                    
                    \App\Models\Permission::create([
                        'name' => $permissionName,
                        'guard_name' => 'web'
                    ]);

                    $this->loadData();

                    \Filament\Notifications\Notification::make()
                        ->title('Permission Added')
                        ->body("Successfully added '{$action}' to module '{$module}'.")
                        ->success()
                        ->send();
                }),

            CreateAction::make('addRole')
                ->label('Add Role')
                ->slideOver()
                ->form([
                    TextInput::make('name')->required()->unique('roles', 'name'),
                ])
                ->action(function ($data) {
                    Role::create(['name' => $data['name'], 'guard_name' => 'web']);
                    $this->loadData();
                }),
        ];
    }
}
