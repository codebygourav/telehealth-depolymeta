<?php

namespace Database\Seeders;

use App\Enums\{BloodGroupOption, GenderOption, MaritalStatus};
use App\Models\{Patient, User};
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use App\Enums\AuthStatus;

class PatientSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::where('email', 'superadmin@telehealth.com')->first();

        // Example demo patients data
        $firstNames = ['Suresh', 'Rina', 'Alok', 'Meena', 'Amit', 'Divya', 'Vikram', 'Sunita', 'Yash', 'Pooja'];
        $lastNames = ['Kumar', 'Sharma', 'Singh', 'Patel', 'Das', 'Reddy', 'Jain', 'Ghosh', 'Mehra', 'Kapoor'];
        $cities = ['Mumbai', 'Delhi', 'Bangalore', 'Hyderabad', 'Ahmedabad', 'Chennai', 'Kolkata', 'Surat', 'Pune', 'Lucknow'];
        $states = ['Maharashtra', 'Delhi', 'Karnataka', 'Telangana', 'Gujarat', 'Tamil Nadu', 'West Bengal', 'Gujarat', 'Maharashtra', 'Uttar Pradesh'];
        $nationalities = ['Indian'];

        $count = 12;

        for ($i = 0; $i < $count; $i++) {
            $gender = GenderOption::cases()[$i % count(GenderOption::cases())]->value;
            $maritalStatus = MaritalStatus::cases()[$i % count(MaritalStatus::cases())]->value;
            $bloodGroup = BloodGroupOption::cases()[$i % count(BloodGroupOption::cases())]->value;
            $firstName = $firstNames[$i % count($firstNames)];
            $lastName = $lastNames[$i % count($lastNames)];
            $city = $cities[$i % count($cities)];
            $state = $states[$i % count($states)];
            $nationality = $nationalities[0];
            $age = rand(10, 70);
            $dob = now()->subYears($age)->subDays(rand(0, 364))->format('Y-m-d');

            // Optionally, create user record for patient
            $user = User::firstOrNew(['email' => 'patient' . ($i + 1) . '@example.com']);
            if (! $user->exists) {
                $user->name = "{$firstName} {$lastName}";
                $user->slug = Str::slug($user->name);
                $user->email_verified_at = now();
                $user->phone = '91100' . rand(100000, 999999);
                $user->password = Hash::make('password');
                $user->status = 'registered';
                $user->save();
                $user->update([
                    'created_by' => $admin?->id ?? null,
                    'updated_by' => $admin?->id ?? null,
                ]);
                try {
                    $user->assignRole('patient');
                } catch (\Throwable $e) {
                }
            }

            Patient::updateOrCreate(
                ['email' => $user->email],
                [
                    'id' => Patient::where('email', $user->email)->value('id') ?? Str::uuid(),
                    'user_id' => $user->id ?? null,
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                    'gender' => $gender,
                    'date_of_birth' => $dob,
                    'age' => $age,
                    'father_name' => 'Father ' . $firstName,
                    'wife_name' => $gender === 'male' ? 'Wife ' . $firstName : null,
                    'husband_name' => $gender === 'female' ? 'Husband ' . $firstName : null,
                    'mobile_no' => '99999' . rand(10000, 99999),
                    'alternate_no' => null,
                    'address' => 'Sample Address, ' . $city,
                    'pincode' => str_pad(strval(rand(100000, 999999)), 6, '0', STR_PAD_LEFT),
                    'area' => 'Area ' . ($i + 1),
                    'city' => $city,
                    'state' => $state,
                    'nationality' => $nationality,
                    'marital_status' => $maritalStatus,
                    'blood_group' => $bloodGroup,
                    'is_existing_patient' => false,
                    'existing_patient_id' => null,
                    'source' => 'app',
                    'create_user_account' => true,
                    'created_by' => $admin?->id ?? null,
                    'updated_by' => $admin?->id ?? null,
                    'deleted_by' => null,
                ]
            );
        }
    }
}
