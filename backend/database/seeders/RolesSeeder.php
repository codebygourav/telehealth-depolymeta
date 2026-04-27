<?php

namespace Database\Seeders;

use Illuminate\Support\Facades\Log;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\{Role, Permission};
use Filament\Facades\Filament;
use App\Filament\Pages\RolePermissionMatrix;

class RolesSeeder extends Seeder
{
    public function run(): void
    {


        $prefixes = RolePermissionMatrix::getPermissionPrefixes();

        // Get Filament resources and pages to build module names
        $resources = Filament::getResources();
        $pages = Filament::getPages();
        $allModules = array_merge($resources, $pages);

        foreach ($allModules as $class) {
            $module = method_exists($class, 'getSlug')
                ? $class::getSlug()
                : strtolower(class_basename($class));

            foreach ($prefixes as $action) {
                $permissionName = "{$module}.{$action}";

                Permission::firstOrCreate([
                    'name' => $permissionName,
                    'guard_name' => 'web',
                ]);
            }
        }

        // Roles (only the minimal set you requested)
        $roles = [
            'super_admin',
            'admin',
            'doctor',
        ];

        foreach ($roles as $roleName) {
            Role::firstOrCreate([
                'name' => $roleName,
                'guard_name' => 'web',
            ]);
        }

        // Assign permissions
        $superAdmin = Role::where('name', 'super_admin')->first();
        if ($superAdmin) {
            $superAdmin->syncPermissions(Permission::all());
        }
        Log::info('RolesSeeder: seeded permissions and roles.');
    }
}
