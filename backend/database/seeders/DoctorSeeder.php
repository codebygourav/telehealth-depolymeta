<?php

namespace Database\Seeders;

use App\Enums\{BloodGroupOption, DepartmentRole, GenderOption, MaritalStatus};
use App\Enums\{LanguageOption};
use App\Models\{Department, DepartmentDoctor, Doctor, User};
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DoctorSeeder extends Seeder
{
    public function run(): void
    {
        $doctors = [
            [
                'first_name' => 'M Joseph',
                'last_name' => 'John',
                'email' => 'mjoseph@gmail.com',
                'gender' => GenderOption::MALE->value,
                'department_name' => 'Oral and Maxillofacial Surgery',
                'city' => 'Ludhiana',
                'state' => 'Punjab',
                'career_start_year' => 2022,
                'sub_title' => 'MD, DM, MBA (HHSM)',
                'medical_license_number' => '13968-A',
                'languages_known' => [LanguageOption::ENGLISH->value, LanguageOption::HINDI->value, LanguageOption::PUNJABI->value, LanguageOption::TELUGU->value],
                'bio' => 'Iam an OMF surgeon specializing in head and neck surgery performing minor and major maxillofacial surgery along with Oral oncology cases at the department of OMFS, CDC.',
                'description' => 'OMF surgeon specializing in head and neck surgery, oral oncology, and major maxillofacial procedures at the department of OMFS, CDC.',
                'specializations_info' => 'Head and neck surgery, Oral and Maxillofacial surgery',
                'key_procedures_info' => 'Oncological resections, flap surgeries, dental implants, third molar surgery, maxillofacial trauma, cyst enucleation, odontogenic tumour removal',
                'memberships_info' => 'Life member - Association of Oral & Maxillofacial Surgeons of India',
                'availability_info' => 'Tuesday, Thursday',
                'professional_experience_info' => [
                    [
                        'title' => 'Senior Residency',
                        'institution' => 'OMFS, CDC',
                        'year_started' => '2022',
                        'description' => 'Focused clinical work in oral oncology, head and neck surgery, and maxillofacial reconstruction.',
                    ],
                ],
            ],
            [
                'first_name' => 'Bobby',
                'last_name' => 'John',
                'email' => 'kjoseph@gmail.com',
                'gender' => GenderOption::MALE->value,
                'department_name' => 'Orthopedics',
                'city' => 'Ludhiana',
                'sub_title' => 'MBBS, MS (Orthopaedics), DNB, FRCS (Edin)',
                'state' => 'Punjab',
                'career_start_year' => 1989,
                'medical_license_number' => '37510',
                'languages_known' => [LanguageOption::ENGLISH->value, LanguageOption::HINDI->value, LanguageOption::PUNJABI->value, LanguageOption::MALAYALAM->value],
                'bio' => 'With 38 years of experience delivering comprehensive orthopaedic excellence, Prof. Dr. Bobby John brings specialized expertise across the full spectrum of musculoskeletal care — from complex trauma reconstruction and limb-threatening infections, to precision arthroplasty and delicate pediatric orthopaedics. His practice unites three decades of surgical mastery with compassionate, evidence-based treatment for patients at every stage of life.',
                'description' => 'Senior orthopaedic surgeon with broad expertise across trauma reconstruction, arthroplasty, paediatric orthopaedics, bone and joint infections, and evidence-based musculoskeletal care.',
                'specializations_info' => 'Trauma surgery, Arthroplasty, Paediatric Orthopaedics, Bone and Joint infections',
                'key_procedures_info' => 'Trauma and neglected trauma, hip and knee replacement surgery, bone and joint infections, non-operative care of the back, orthopaedic rheumatology',
                'memberships_info' => 'Indian Orthopaedic Association, Paediatric Orthopaedic Association of India, A.O Trauma International',
                'availability_info' => 'OPD Schedule Monday and Thursday (9:00 AM to 1:00 PM) with appointment, Wednesday 3:00 PM to 5:00 PM for children, evening clinic Monday and Thursday 4:00 PM to 6:00 PM by appointment, telemedicine Tuesday and Friday 5:00 PM to 7:00 PM by appointment.',
                'professional_experience_info' => [
                    [
                        'title' => 'Senior Residency',
                        'institution' => 'Orthopaedics',
                        'year_started' => '1989',
                        'description' => 'Long-standing orthopaedic practice focused on trauma, arthroplasty, infections, and paediatric orthopaedics.',
                    ],
                ],
            ],
        ];

        foreach ($doctors as $index => $entry) {
            $user = User::firstOrNew(['email' => strtolower($entry['email'])]);
            $user->name = trim($entry['first_name'] . ' ' . $entry['last_name']);
            $user->slug = Str::slug($user->name);
            $user->email_verified_at = now();
            $user->phone = '98' . str_pad((string) ($index + 1), 8, '0', STR_PAD_LEFT);
            $user->password = Hash::make('password');
            $user->status = 'active';
            $user->save();

            try {
                $user->assignRole('doctor');
            } catch (\Throwable $e) {
            }

            $doctor = Doctor::firstOrNew(['user_id' => $user->id]);
            $doctor->fill([
                'first_name' => $entry['first_name'],
                'last_name' => $entry['last_name'],
                'gender' => $entry['gender'],
                'dob' => Carbon::now()->subYears(45 + ($index * 12))->format('Y-m-d'),
                'marital_status' => MaritalStatus::MARRIED->value,
                'blood_group' => BloodGroupOption::A_POSITIVE->value,
                'career_start_year' => $entry['career_start_year'],
                'medical_license_number' => $entry['medical_license_number'],
                'bio' => $entry['bio'],
                'sub_title' => $entry['sub_title'] ?? null,
                'description' => $entry['description'],
                'address_line1' => 'Christian Medical College, Ludhiana',
                'country' => 'India',
                'state' => $entry['state'],
                'city' => $entry['city'],
                'pincode' => '141008',
                'languages_known' => $entry['languages_known'],
                'specializations_info' => $entry['specializations_info'],
                'key_procedures_info' => $entry['key_procedures_info'],
                'memberships_info' => $entry['memberships_info'],
                'availability_info' => $entry['availability_info'],
                'professional_experience_info' => $entry['professional_experience_info'],
                'education_info' => [],
                'fellowships_info' => [],
                'certifications_info' => [],
                'status' => \App\Enums\DoctorStatus::ACTIVE->value,
                'avatar' => asset('images/old-user-avatar.png'),
            ]);
            $doctor->save();

            $department = Department::firstOrCreate(
                ['name' => $entry['department_name']],
                [
                    'description' => $entry['department_name'] . ' department',
                    'is_tab_layout' => false,
                    'department_stamp' => null,
                    'symptom_ids' => [],
                ]
            );

            DepartmentDoctor::updateOrCreate(
                ['doctor_id' => $doctor->id, 'department_id' => $department->id],
                ['role' => DepartmentRole::SeniorConsultant->value, 'order' => 1]
            );
        }
    }
}
