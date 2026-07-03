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
            [
                'first_name' => 'Bobby',
                'last_name' => 'John',
                'city' => 'Ludhiana',
                'state' => 'Punjab',
                'speciality' => 'Orthopedics',
                'bio' => 'Orthopedic surgeon with a focus on joint care, trauma recovery, and mobility restoration.',
                'avatar' => asset('images/old-user-avatar.png'),
                'education_info' => [
                    ['degree' => 'MBBS', 'institution' => 'Christian Medical College, Ludhiana', 'start_date' => '1982', 'end_date' => '1987'],
                    ['degree' => 'MS Orthopedics', 'institution' => 'PGIMER, Chandigarh', 'start_date' => '1988', 'end_date' => '1991'],
                ],
            ],
            [
                'first_name' => 'Meera',
                'last_name' => 'Patel',
                'city' => 'Ahmedabad',
                'state' => 'Gujarat',
                'speciality' => 'Pediatrics',
                'bio' => 'Pediatric consultant caring for newborns, children, and adolescent preventive health.',
                'avatar' => asset('images/old-user-avatar.png'),
                'education_info' => [
                    ['degree' => 'MBBS', 'institution' => 'B.J. Medical College, Ahmedabad', 'start_date' => '1989', 'end_date' => '1994'],
                    ['degree' => 'MD Pediatrics', 'institution' => 'Seth G.S. Medical College, Mumbai', 'start_date' => '1995', 'end_date' => '1998'],
                ],
            ],
            [
                'first_name' => 'Arjun',
                'last_name' => 'Nair',
                'city' => 'Kochi',
                'state' => 'Kerala',
                'speciality' => 'Cardiology',
                'bio' => 'Interventional cardiologist with experience in preventive cardiology and OPD follow-up care.',
                'avatar' => asset('images/old-user-avatar.png'),
                'education_info' => [
                    ['degree' => 'MBBS', 'institution' => 'Government Medical College, Thrissur', 'start_date' => '1987', 'end_date' => '1992'],
                    ['degree' => 'DM Cardiology', 'institution' => 'Sree Chitra Tirunal Institute, Thiruvananthapuram', 'start_date' => '1996', 'end_date' => '1999'],
                ],
            ],
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
                'bio' => $entry['bio'],
                'description' => "Specializes in {$speciality} with compassionate OPD care.",
                'address_line1' => "Address Line 1 - {$entry['city']}",
                'avatar' => $entry['avatar'] ?? null,
                'education_info' => $entry['education_info'],
                'fellowships_info' => [
                    [
                        'title' => "{$speciality} Clinical Fellowship",
                        'institution' => "{$entry['city']} Medical Institute",
                        'year_started' => (string) (now()->year - rand(10, 18)),
                        'description' => "Focused fellowship training in {$speciality}.",
                    ],
                ],
                'certifications_info' => [
                    [
                        'name' => "{$speciality} Board Certification",
                        'organization' => 'National Medical Board',
                        'issue_date' => now()->copy()->subYears(rand(2, 8))->format('Y-m-d'),
                        'expiry_date' => now()->copy()->addYears(rand(1, 4))->format('Y-m-d'),
                    ],
                ],
                'country' => 'India',
                'state' => $entry['state'],
                'city' => $entry['city'],
                'pincode' => str_pad(strval(100000 + ($i * 311)), 6, '0', STR_PAD_LEFT),
                'languages_known' => ['english', 'hindi', 'punjabi'],
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
