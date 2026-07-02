<?php

namespace Database\Seeders;

use App\Enums\{BloodGroupOption, DepartmentRole, GenderOption, MaritalStatus};
use App\Models\{Department, DepartmentDoctor, Doctor, User};
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;


class DoctorSeeder extends Seeder
{
    public function run(): void
    {
        $departments = Department::all();
        $departmentsCount = $departments->count() > 0 ? $departments->count() : 1;
        $doctors = [
            ['first_name' => 'Aarav', 'last_name' => 'Malhotra', 'city' => 'Mumbai', 'state' => 'Maharashtra', 'speciality' => 'Gynecology'],
            ['first_name' => 'Neha', 'last_name' => 'Kapoor', 'city' => 'Bengaluru', 'state' => 'Karnataka', 'speciality' => 'Cardiology'],
            ['first_name' => 'Ravi', 'last_name' => 'Sharma', 'city' => 'New Delhi', 'state' => 'Delhi', 'speciality' => 'Orthopedics'],
            ['first_name' => 'Meera', 'last_name' => 'Sethi', 'city' => 'Hyderabad', 'state' => 'Telangana', 'speciality' => 'Dermatology'],
        ];

        foreach ($doctors as $i => $entry) {
            $first_name = $entry['first_name'];
            $last_name = $entry['last_name'];
            $email = strtolower($first_name) . '.' . strtolower($last_name) . '@deploymeta.test';

            $user = User::firstOrNew(['email' => $email]);
            $user->name = "{$first_name} {$last_name}";
            $user->slug = Str::slug($user->name);
            $user->email_verified_at = now();
            $user->phone = '98' . rand(10000000, 99999999);
            $user->password = Hash::make('password');
            $user->status = 'active';
            $user->save();

            try {
                $user->assignRole('doctor');
            } catch (\Throwable $e) {
            }

            $speciality = $entry['speciality'];
            $dob = Carbon::now()->subYears(rand(30, 60))->format('Y-m-d');

            $doctor = Doctor::firstOrNew(['user_id' => $user->id]);
            $doctor->fill([
                'first_name' => $first_name,
                'last_name' => $last_name,
                'gender' => ($i % 2 === 0 ? GenderOption::MALE : GenderOption::FEMALE)->value,
                'dob' => $dob,
                'marital_status' => MaritalStatus::MARRIED->value,
                'blood_group' => BloodGroupOption::A_POSITIVE->value,
                'medical_license_number' => 'MD-' . strtoupper(substr($speciality, 0, 3)) . '-' . str_pad($i + 1, 3, '0', STR_PAD_LEFT),
                'years_experience' => rand(5, 25),
                'bio' => "Qualified $speciality expert.",
                'description' => "Specializes in $speciality.",
                'address_line1' => "Address Line 1 - {$entry['city']}",
                'country' => 'India',
                'state' => $entry['state'],
                'city' => $entry['city'],
                'pincode' => str_pad(strval(100000 + ($i * 311)), 6, '0', STR_PAD_LEFT),
                'languages_known' => 'english,hindi',
                'status' => \App\Enums\DoctorStatus::ACTIVE->value,
            ]);
            $doctor->save();

            if ($departmentsCount > 0) {
                $primaryIndex = ($i * 2) % $departmentsCount;
                $secondaryIndex = ($primaryIndex + 1) % $departmentsCount;
                $deptIds = array_unique([$departments[$primaryIndex]->id, $departments[$secondaryIndex]->id]);

                foreach ($deptIds as $index => $deptId) {
                    DepartmentDoctor::updateOrCreate(
                        ['doctor_id' => $doctor->id, 'department_id' => $deptId],
                        ['role' => DepartmentRole::SeniorConsultant->value, 'order' => $index + 1]
                    );
                }
            }
        }
    }
}
