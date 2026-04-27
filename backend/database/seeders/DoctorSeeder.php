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

        $firstNames = ['Amit', 'Priya', 'Rahul', 'Sunita', 'Vikram'];
        $lastNames = ['Sharma', 'Nair', 'Verma', 'Patel', 'Singh'];
        $cities = ['Mumbai', 'Bengaluru', 'New Delhi', 'Hyderabad', 'Chennai'];
        $states = ['Maharashtra', 'Karnataka', 'Delhi', 'Telangana', 'Tamil Nadu'];

        $specialities = ['Cardiology', 'Neurology', 'Paediatrics', 'Dermatology', 'Orthopaedics'];

        for ($i = 0; $i < 1; $i++) {
            $first_name = $firstNames[$i];
            $last_name = $lastNames[$i];
            $email = strtolower($first_name) . '.' . strtolower($last_name) . '@telehealth.test';

            // USER CREATE OR UPDATE
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

            $speciality = $specialities[$i];
            $dob = Carbon::now()->subYears(rand(30, 60))->format('Y-m-d');

            // DOCTOR PROFILE
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
                'address_line1' => "Address Line 1 - $cities[$i]",
                'country' => 'India',
                'state' => $states[$i],
                'city' => $cities[$i],
                'pincode' => str_pad(strval(100000 + ($i * 311)), 6, '0', STR_PAD_LEFT),
                'languages_known' => 'english,hindi',
                'status' => \App\Enums\DoctorStatus::ACTIVE->value,
            ]);
            $doctor->save();

            // DEPARTMENTS
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
