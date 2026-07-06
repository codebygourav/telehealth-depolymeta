<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use App\Enums\UserRole;
use Illuminate\Support\Str;


class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Ensure roles exist using enum
        foreach (UserRole::values() as $roleName) {
            Role::firstOrCreate([
                'name' => $roleName,
                'guard_name' => 'web',
            ]);
        }
        

        // Create or update super admin user
        $user = User::firstOrCreate(
            ['email' => 'quickcoderzindia@gmail.com'],
            [
                'name' => 'Super Admin',
                'password' => bcrypt('uFx5vs4WmXyjQpmz'), // Change this password after seeding
                'email_verified_at' => now(),
                'remember_token' => Str::random(10),
            ]
        );

        // Now we have user ID — update audit fields
        $user->created_by = $user->id;
        $user->updated_by = $user->id;
        $user->deleted_by = null;
        $user->save();

        // Assign enum role
        $user->assignRole(UserRole::SuperAdmin->value);
    }
}