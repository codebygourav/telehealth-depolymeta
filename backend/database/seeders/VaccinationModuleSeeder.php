<?php

namespace Database\Seeders;

use Illuminate\Support\Facades\Log;

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
                'disease_for' => 'Tuberculosis',
                'description' => 'Protects against severe forms of tuberculosis.',
                'side_effects' => 'Small scar at site, mild fever',
                'dosage_information' => '0.05 ml',
            ],
            'hepb' => [
                'name' => 'Hepatitis B Vaccine',
                'short_name' => 'HepB',
                'disease_for' => 'Hepatitis B',
                'description' => 'Protects against Hepatitis B infection.',
                'side_effects' => 'Mild fever, soreness at site',
                'dosage_information' => '0.5 ml',
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
                'name' => 'Demo 2 Vaccine Schedule',
                'description' => 'Demo schedule with 2 vaccinations.',
                'items' => [
                    [
                        'vaccine' => 'bcg',
                        'set' => 'Set 1 (Birth)',
                        'dose' => 1,
                        'age' => 'At Birth',
                        'months' => 0,
                        'days' => 0,
                        'sort' => 1,
                    ],
                    [
                        'vaccine' => 'hepb',
                        'set' => 'Set 1 (Birth)',
                        'dose' => 1,
                        'age' => 'At Birth',
                        'months' => 0,
                        'days' => 0,
                        'sort' => 2,
                    ],
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
                    'depends_on_previous_dose' => false,
                    'interval_days' => 0,
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
                'profile' => 'self',
                'template' => 'baby',
                'start_date' => now()->subMonths(1)->toDateString(),
                'status' => PatientVaccinationProgramStatus::ACTIVE,
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
            ->take(2)
            ->get();

        $statusPlan = [
            VaccinationStatus::COMPLETED,
            VaccinationStatus::SCHEDULED,
        ];

        foreach ($rows as $index => $row) {
            $status = $statusPlan[$index] ?? VaccinationStatus::SCHEDULED;
            $completed = $status === VaccinationStatus::COMPLETED;

            $row->update([
                'status' => $status->value,
                'completed_date' => $completed ? now()->subDays(5 - $index)->toDateString() : null,
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
Log::info('2 demo vaccinations were assigned to doctor\'s patient.');