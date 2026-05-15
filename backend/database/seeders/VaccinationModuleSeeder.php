<?php

namespace Database\Seeders;

use App\Enums\BloodGroupOption;
use App\Enums\DoctorStatus;
use App\Enums\GenderOption;
use App\Enums\MaritalStatus;
use App\Enums\PatientProfileType;
use App\Enums\PatientVaccinationProgramStatus;
use App\Enums\VaccinationDocumentType;
use App\Enums\VaccinationGenderRestriction;
use App\Enums\VaccinationProgramTargetType;
use App\Enums\VaccinationStatus;
use App\Models\Doctor;
use App\Models\Patient;
use App\Models\PatientProfile;
use App\Models\PatientVaccination;
use App\Models\PatientVaccinationProgram;
use App\Models\User;
use App\Models\Vaccination;
use App\Models\VaccinationDocument;
use App\Models\VaccinationProgram;
use App\Models\VaccinationTemplate;
use App\Models\VaccinationTemplateItem;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class VaccinationModuleSeeder extends Seeder
{
    public function run(): void
    {
        $doctor = $this->doctor();
        $patient = $this->patient();

        $vaccines = $this->vaccines();
        $programs = $this->programs();
        $profiles = $this->profiles($patient);
        $templates = $this->templates($doctor, $programs, $vaccines);

        $this->assignments($doctor, $profiles, $templates);
        $this->documents();

        $this->call(VaccinationModuleContentSeeder::class);
    }

    private function doctor(): Doctor
    {
        $doctor = Doctor::query()->first();
        if ($doctor) {
            return $doctor;
        }

        $user = User::firstOrCreate(
            ['email' => 'vaccination.doctor@example.com'],
            [
                'name' => 'Dr. Vaccination Demo',
                'slug' => 'dr-vaccination-demo',
                'phone' => '9800000001',
                'email_verified_at' => now(),
                'password' => Hash::make('password'),
                'status' => 'active',
            ]
        );

        return Doctor::firstOrCreate(
            ['user_id' => $user->id],
            [
                'first_name' => 'Vaccination',
                'last_name' => 'Doctor',
                'gender' => GenderOption::MALE->value,
                'dob' => now()->subYears(38)->toDateString(),
                'marital_status' => MaritalStatus::MARRIED->value,
                'blood_group' => BloodGroupOption::A_POSITIVE->value,
                'medical_license_number' => 'VAC-DEMO-001',
                'years_experience' => 12,
                'bio' => 'Demo doctor for vaccination schedules.',
                'description' => 'Handles demo vaccination schedules and patient dose tracking.',
                'country' => 'India',
                'state' => 'Maharashtra',
                'city' => 'Mumbai',
                'pincode' => '400001',
                'languages_known' => ['english', 'hindi'],
                'status' => DoctorStatus::ACTIVE->value,
            ]
        );
    }

    private function patient(): Patient
    {
        $patient = Patient::query()->first();
        if ($patient) {
            return $patient;
        }

        $user = User::firstOrCreate(
            ['email' => 'vaccination.patient@example.com'],
            [
                'name' => 'Suresh Kumar',
                'slug' => 'suresh-kumar-vaccination',
                'phone' => '9900000001',
                'email_verified_at' => now(),
                'password' => Hash::make('password'),
                'status' => 'registered',
            ]
        );

        return Patient::firstOrCreate(
            ['user_id' => $user->id],
            [
                'first_name' => 'Suresh',
                'last_name' => 'Kumar',
                'gender' => GenderOption::MALE->value,
                'date_of_birth' => now()->subYears(34)->toDateString(),
                'age' => 34,
                'mobile_no' => '9999900001',
                'email' => $user->email,
                'address' => 'Demo vaccination address',
                'city' => 'Mumbai',
                'state' => 'Maharashtra',
                'pincode' => '400001',
                'nationality' => 'Indian',
                'marital_status' => MaritalStatus::MARRIED->value,
                'blood_group' => BloodGroupOption::O_POSITIVE->value,
                'source' => 'app',
                'is_existing_patient' => false,
                'create_user_account' => true,
            ]
        );
    }

    /**
     * @return array<string, Vaccination>
     */
    private function vaccines(): array
    {
        $records = [
            'bcg' => [
                'name' => 'BCG Vaccine',
                'short_name' => 'BCG',
                'manufacturer' => 'Demo Biologics',
                'disease_for' => 'Tuberculosis',
                'description' => 'Protects against severe forms of tuberculosis.',
                'side_effects' => 'Small scar at site, mild fever',
                'dosage_information' => '0.05 ml',
                'is_multi_dose' => false,
                'total_doses' => 1,
                'minimum_age_days' => 0,
                'maximum_age_days' => 365,
                'gender_restriction' => VaccinationGenderRestriction::ALL->value,
            ],
            'hepb' => [
                'name' => 'Hepatitis B Vaccine',
                'short_name' => 'HepB',
                'manufacturer' => 'Demo Pharma',
                'disease_for' => 'Hepatitis B',
                'description' => 'Protects against Hepatitis B infection.',
                'side_effects' => 'Mild fever, soreness at site',
                'dosage_information' => '0.5 ml',
                'is_multi_dose' => true,
                'total_doses' => 3,
                'minimum_age_days' => 0,
                'maximum_age_days' => null,
                'gender_restriction' => VaccinationGenderRestriction::ALL->value,
            ],
            'opv' => [
                'name' => 'Oral Polio Vaccine',
                'short_name' => 'OPV',
                'manufacturer' => 'Demo Oral Care',
                'disease_for' => 'Poliomyelitis',
                'description' => 'Oral vaccine for polio protection.',
                'side_effects' => 'Mild fever, fussiness',
                'dosage_information' => '2 drops',
                'is_multi_dose' => true,
                'total_doses' => 3,
                'minimum_age_days' => 0,
                'maximum_age_days' => null,
                'gender_restriction' => VaccinationGenderRestriction::ALL->value,
            ],
            'tdap' => [
                'name' => 'Tdap Vaccine',
                'short_name' => 'Tdap',
                'manufacturer' => 'Demo Immuno',
                'disease_for' => 'Tetanus, diphtheria, pertussis',
                'description' => 'Booster vaccine for adults and pregnancy schedules.',
                'side_effects' => 'Local pain, low-grade fever',
                'dosage_information' => '0.5 ml',
                'is_multi_dose' => false,
                'total_doses' => 1,
                'minimum_age_days' => 3650,
                'maximum_age_days' => null,
                'gender_restriction' => VaccinationGenderRestriction::ALL->value,
            ],
            'influenza' => [
                'name' => 'Influenza Vaccine',
                'short_name' => 'Flu',
                'manufacturer' => 'Demo Seasonal',
                'disease_for' => 'Influenza',
                'description' => 'Seasonal influenza protection.',
                'side_effects' => 'Soreness, mild fever',
                'dosage_information' => '0.5 ml',
                'is_multi_dose' => false,
                'total_doses' => 1,
                'minimum_age_days' => 180,
                'maximum_age_days' => null,
                'gender_restriction' => VaccinationGenderRestriction::ALL->value,
            ],
            'typhoid' => [
                'name' => 'Typhoid Vaccine',
                'short_name' => 'Typhoid',
                'manufacturer' => 'Demo Travel',
                'disease_for' => 'Typhoid fever',
                'description' => 'Travel vaccine for typhoid fever protection.',
                'side_effects' => 'Mild pain, fever',
                'dosage_information' => '0.5 ml',
                'is_multi_dose' => false,
                'total_doses' => 1,
                'minimum_age_days' => 730,
                'maximum_age_days' => null,
                'gender_restriction' => VaccinationGenderRestriction::ALL->value,
            ],
        ];

        $vaccines = [];
        foreach ($records as $key => $record) {
            $vaccines[$key] = Vaccination::updateOrCreate(
                ['name' => $record['name']],
                array_merge($record, [
                    'contraindications' => 'Severe allergy to previous dose',
                    'precautions' => 'Check allergy and fever history before administration',
                    'is_active' => true,
                ])
            );
        }

        return $vaccines;
    }

    /**
     * @return array<string, VaccinationProgram>
     */
    private function programs(): array
    {
        $records = [
            'baby' => ['Baby Immunization', VaccinationProgramTargetType::BABY],
            'pregnancy' => ['Pregnancy Vaccination', VaccinationProgramTargetType::PREGNANCY],
            'adult' => ['Adult Vaccination', VaccinationProgramTargetType::ADULT],
            'elderly' => ['Elderly Vaccination', VaccinationProgramTargetType::ELDERLY],
            'travel' => ['Travel Vaccination', VaccinationProgramTargetType::TRAVEL],
        ];

        $programs = [];
        foreach ($records as $key => [$name, $type]) {
            $programs[$key] = VaccinationProgram::updateOrCreate(
                ['slug' => Str::slug($name)],
                [
                    'name' => $name,
                    'description' => "{$type->label()} demo program.",
                    'target_type' => $type->value,
                    'is_active' => true,
                ]
            );
        }

        return $programs;
    }

    /**
     * @return array<string, PatientProfile>
     */
    private function profiles(Patient $patient): array
    {
        $records = [
            'self' => [
                'name' => trim("{$patient->first_name} {$patient->last_name}") ?: 'Self',
                'profile_type' => PatientProfileType::SELF,
                'date_of_birth' => $patient->date_of_birth,
                'gender' => GenderOption::MALE->value,
                'is_primary' => true,
            ],
            'baby' => [
                'name' => 'Baby Aryan',
                'profile_type' => PatientProfileType::BABY,
                'date_of_birth' => now()->subMonths(8)->toDateString(),
                'gender' => GenderOption::MALE->value,
                'weight' => 8.5,
                'height' => 70,
            ],
            'pregnancy' => [
                'name' => 'Wife Pregnancy',
                'profile_type' => PatientProfileType::PREGNANCY,
                'gender' => GenderOption::FEMALE->value,
                'pregnancy_due_date' => now()->addMonths(4)->toDateString(),
                'weight' => 62,
                'height' => 158,
            ],
            'father' => [
                'name' => 'Father Kumar',
                'profile_type' => PatientProfileType::ELDERLY,
                'date_of_birth' => now()->subYears(68)->toDateString(),
                'gender' => GenderOption::MALE->value,
                'weight' => 72,
                'height' => 168,
            ],
            'travel' => [
                'name' => 'Self Travel Profile',
                'profile_type' => PatientProfileType::ADULT,
                'date_of_birth' => $patient->date_of_birth,
                'gender' => GenderOption::MALE->value,
                'weight' => 76,
                'height' => 172,
            ],
        ];

        $profiles = [];
        foreach ($records as $key => $record) {
            $profiles[$key] = PatientProfile::updateOrCreate(
                [
                    'patient_id' => $patient->id,
                    'name' => $record['name'],
                ],
                [
                    'profile_type' => $record['profile_type']->value,
                    'date_of_birth' => $record['date_of_birth'] ?? null,
                    'gender' => $record['gender'] ?? null,
                    'pregnancy_due_date' => $record['pregnancy_due_date'] ?? null,
                    'blood_group' => BloodGroupOption::O_POSITIVE->value,
                    'weight' => $record['weight'] ?? null,
                    'height' => $record['height'] ?? null,
                    'is_primary' => $record['is_primary'] ?? false,
                ]
            );
        }

        return $profiles;
    }

    /**
     * @param array<string, VaccinationProgram> $programs
     * @param array<string, Vaccination> $vaccines
     * @return array<string, VaccinationTemplate>
     */
    private function templates(Doctor $doctor, array $programs, array $vaccines): array
    {
        $templates = [
            'baby' => [
                'program' => 'baby',
                'name' => 'WHO Child Schedule Demo',
                'description' => 'Demo child schedule with birth and follow-up doses.',
                'items' => [
                    ['vaccine' => 'bcg', 'set' => 'Set 1 (Birth)', 'dose' => 1, 'age' => 'At Birth', 'months' => 0, 'days' => 0, 'sort' => 1],
                    ['vaccine' => 'hepb', 'set' => 'Set 1 (Birth)', 'dose' => 1, 'age' => 'At Birth', 'months' => 0, 'days' => 0, 'sort' => 2],
                    ['vaccine' => 'opv', 'set' => 'Set 2 (6 Weeks)', 'dose' => 1, 'age' => '6 Weeks', 'months' => 1, 'days' => 14, 'sort' => 1],
                ],
            ],
            'pregnancy' => [
                'program' => 'pregnancy',
                'name' => 'Pregnancy Vaccine Demo',
                'description' => 'Demo pregnancy vaccination schedule.',
                'items' => [
                    ['vaccine' => 'tdap', 'set' => 'Pregnancy Dose', 'dose' => 1, 'age' => '27-36 Weeks', 'months' => 0, 'days' => 0, 'sort' => 1],
                    ['vaccine' => 'influenza', 'set' => 'Pregnancy Dose', 'dose' => 1, 'age' => 'Any Trimester', 'months' => 0, 'days' => 14, 'sort' => 2],
                ],
            ],
            'adult' => [
                'program' => 'adult',
                'name' => 'Adult Booster Demo',
                'description' => 'Demo adult booster schedule.',
                'items' => [
                    ['vaccine' => 'tdap', 'set' => 'Adult Booster', 'dose' => 1, 'age' => 'Adult', 'months' => 0, 'days' => 0, 'sort' => 1],
                    ['vaccine' => 'influenza', 'set' => 'Annual Vaccine', 'dose' => 1, 'age' => 'Yearly', 'months' => 1, 'days' => 0, 'sort' => 2],
                ],
            ],
            'elderly' => [
                'program' => 'elderly',
                'name' => 'Elderly Protection Demo',
                'description' => 'Not assigned demo template.',
                'items' => [
                    ['vaccine' => 'influenza', 'set' => 'Elderly Annual', 'dose' => 1, 'age' => '65+ Years', 'months' => 0, 'days' => 0, 'sort' => 1],
                ],
            ],
            'travel' => [
                'program' => 'travel',
                'name' => 'Travel Vaccine Demo',
                'description' => 'Not assigned demo travel template.',
                'items' => [
                    ['vaccine' => 'typhoid', 'set' => 'Travel Set', 'dose' => 1, 'age' => 'Before Travel', 'months' => 0, 'days' => 0, 'sort' => 1],
                ],
            ],
        ];

        $created = [];
        foreach ($templates as $key => $templateData) {
            $template = VaccinationTemplate::updateOrCreate(
                [
                    'doctor_id' => $doctor->id,
                    'name' => $templateData['name'],
                ],
                [
                    'vaccination_program_id' => $programs[$templateData['program']]->id,
                    'description' => $templateData['description'],
                    'is_active' => true,
                ]
            );

            $template->items()->delete();
            foreach ($templateData['items'] as $index => $item) {
                VaccinationTemplateItem::create([
                    'vaccination_template_id' => $template->id,
                    'vaccination_id' => $vaccines[$item['vaccine']]->id,
                    'set_name' => $item['set'],
                    'set_description' => "Demo {$item['set']} group.",
                    'set_sort_order' => $index + 1,
                    'dose_no' => $item['dose'],
                    'depends_on_previous_dose' => $index > 0 && $key === 'baby',
                    'interval_days' => $index > 0 && $key === 'baby' ? 42 : 0,
                    'interval_months' => 0,
                    'minimum_age_days' => $item['months'] * 30 + $item['days'],
                    'maximum_age_days' => null,
                    'recommended_age_label' => $item['age'],
                    'due_after_months' => $item['months'],
                    'due_after_days' => $item['days'],
                    'sort_order' => $item['sort'],
                ]);
            }

            $created[$key] = $template;
        }

        return $created;
    }

    /**
     * @param array<string, PatientProfile> $profiles
     * @param array<string, VaccinationTemplate> $templates
     */
    private function assignments(Doctor $doctor, array $profiles, array $templates): void
    {
        $assignmentData = [
            [
                'profile' => 'baby',
                'template' => 'baby',
                'start_date' => now()->subMonths(3)->toDateString(),
                'status' => PatientVaccinationProgramStatus::ACTIVE,
            ],
            [
                'profile' => 'pregnancy',
                'template' => 'pregnancy',
                'start_date' => now()->subWeeks(2)->toDateString(),
                'status' => PatientVaccinationProgramStatus::ACTIVE,
            ],
            [
                'profile' => 'self',
                'template' => 'adult',
                'start_date' => now()->subMonth()->toDateString(),
                'status' => PatientVaccinationProgramStatus::COMPLETED,
            ],
        ];

        foreach ($assignmentData as $data) {
            $assignment = PatientVaccinationProgram::updateOrCreate(
                [
                    'patient_profile_id' => $profiles[$data['profile']]->id,
                    'vaccination_template_id' => $templates[$data['template']]->id,
                    'doctor_id' => $doctor->id,
                ],
                [
                    'vaccination_program_id' => $templates[$data['template']]->vaccination_program_id,
                    'start_date' => $data['start_date'],
                    'status' => $data['status']->value,
                ]
            );

            $assignment->generateVaccinationRows();
        }

        $rows = PatientVaccination::query()
            ->where('doctor_id', $doctor->id)
            ->with('vaccination')
            ->orderBy('scheduled_date')
            ->take(7)
            ->get();

        $statusPlan = [
            VaccinationStatus::COMPLETED,
            VaccinationStatus::SCHEDULED,
            VaccinationStatus::PENDING,
            VaccinationStatus::MISSED,
            VaccinationStatus::CANCELLED,
            VaccinationStatus::COMPLETED,
            VaccinationStatus::SCHEDULED,
        ];

        foreach ($rows as $index => $row) {
            $status = $statusPlan[$index] ?? VaccinationStatus::SCHEDULED;
            $completed = $status === VaccinationStatus::COMPLETED;

            $row->update([
                'status' => $status->value,
                'completed_date' => $completed ? now()->subDays(20 - $index)->toDateString() : null,
                'batch_number' => $completed ? 'VAC-BATCH-' . str_pad((string) ($index + 1), 3, '0', STR_PAD_LEFT) : null,
                'route' => $completed ? 'Injection' : null,
                'site' => $completed ? ($index % 2 === 0 ? 'Left thigh' : 'Upper arm') : null,
                'dose_amount' => $completed ? ($row->vaccination?->dosage_information ?? '0.5 ml') : null,
                'given_at' => $completed ? 'Demo Clinic' : null,
                'given_by' => $completed ? 'Dr. Vaccination Demo' : null,
                'doctor_notes' => $completed ? 'Demo dose completed successfully.' : 'Demo pending/scheduled dose.',
                'side_effect_observed' => $completed ? 'No immediate side effects observed.' : null,
                'patient_reaction' => $completed ? 'Normal' : null,
                'reminder_sent' => $index % 2 === 0,
                'last_reminder_sent_at' => $index % 2 === 0 ? now()->subDays(2) : null,
                'reminder_count' => $index % 2 === 0 ? 1 : 0,
                'next_reminder_at' => $status === VaccinationStatus::SCHEDULED ? now()->addDays(1) : null,
            ]);
        }
    }

    private function documents(): void
    {
        PatientVaccination::query()
            ->where('status', VaccinationStatus::COMPLETED->value)
            ->take(2)
            ->get()
            ->each(function (PatientVaccination $dose, int $index) {
                VaccinationDocument::updateOrCreate(
                    [
                        'patient_vaccination_id' => $dose->id,
                        'certificate_number' => 'DEMO-CERT-' . str_pad((string) ($index + 1), 3, '0', STR_PAD_LEFT),
                    ],
                    [
                        'document' => 'vaccinations/demo-certificates/demo-cert-' . ($index + 1) . '.pdf',
                        'document_type' => VaccinationDocumentType::CERTIFICATE->value,
                    ]
                );
            });
    }
}
