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

        // Create super admin
        $user = User::firstOrNew(['email' => 'quickcoderzindia@gmail.com']);
        $user->name = 'Super Admin';
        $user->slug = Str::slug($user->name);
        $user->email_verified_at = Carbon::now();
        $user->password = Hash::make('uFx5vs4WmXyjQpm');
        $user->phone = '9864796436';
        $user->save();

        // Now we have user ID — update audit fields
        $user->update([
            'created_by' => $user->id,
            'updated_by' => $user->id,
            'deleted_by' => null,
        ]);

        // Assign enum role
        $user->assignRole(UserRole::SuperAdmin->value);
    }
}
