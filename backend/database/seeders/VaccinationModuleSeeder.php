<?php

namespace Database\Seeders;

use App\Enums\BloodGroupOption;
use App\Enums\DoctorStatus;
use App\Enums\GenderOption;
use App\Enums\MaritalStatus;
use App\Enums\VaccinationDocumentType;
use App\Enums\VaccinationGenderRestriction;
use App\Enums\VaccinationProgramTargetType;
use App\Enums\VaccinationStatus;
use App\Models\Doctor;
use App\Models\Patient;
use App\Models\PatientVaccination;
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
        $templates = $this->templates($doctor, $programs, $vaccines);

        $this->assignments($doctor, $patient, $templates);
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
                'description' => 'Protects against tuberculosis.',
                'side_effects' => 'Scar at injection site, fever',
                'dosage_information' => '0.05 ml',
            ],
            'hepb' => [
                'name' => 'Hepatitis B Vaccine',
                'short_name' => 'HepB',
                'disease_for' => 'Hepatitis B',
                'description' => 'Protects against Hepatitis B.',
                'side_effects' => 'Soreness, mild fever',
                'dosage_information' => '0.5 ml',
            ],
            'opv' => [
                'name' => 'Oral Polio Vaccine',
                'short_name' => 'OPV',
                'disease_for' => 'Poliomyelitis',
                'description' => 'Oral vaccine for polio protection.',
                'side_effects' => 'Mild fever, fussiness',
                'dosage_information' => '2 drops',
            ],
            'tdap' => [
                'name' => 'Tdap Vaccine',
                'short_name' => 'Tdap',
                'disease_for' => 'Tetanus, Diphtheria, Pertussis',
                'description' => 'Booster immunization against tetanus, diphtheria, and pertussis.',
                'side_effects' => 'Pain at injection site, headache, tiredness',
                'dosage_information' => '0.5 ml',
            ],
            'flu' => [
                'name' => 'Influenza Vaccine (Seasonal)',
                'short_name' => 'Flu',
                'disease_for' => 'Seasonal Influenza',
                'description' => 'Seasonal vaccine protecting against flu strains.',
                'side_effects' => 'Slight soreness, low-grade fever',
                'dosage_information' => '0.5 ml',
            ],
            'mmr' => [
                'name' => 'MMR Vaccine',
                'short_name' => 'MMR',
                'disease_for' => 'Measles, Mumps, Rubella',
                'description' => 'Protects against measles, mumps, and rubella.',
                'side_effects' => 'Fever, mild rash, swollen glands',
                'dosage_information' => '0.5 ml',
            ],
            'pcv' => [
                'name' => 'Pneumococcal Vaccine',
                'short_name' => 'PCV',
                'disease_for' => 'Pneumococcal Disease',
                'description' => 'Protects against meningitis and blood infections.',
                'side_effects' => 'Soreness, redness, irritability',
                'dosage_information' => '0.5 ml',
            ],
            'shingles' => [
                'name' => 'Recombinant Shingles Vaccine',
                'short_name' => 'Shingles',
                'disease_for' => 'Herpes Zoster (Shingles)',
                'description' => 'Protects older adults from shingles and nerve pain.',
                'side_effects' => 'Sore arm, muscle pain, tiredness',
                'dosage_information' => '0.5 ml',
            ],
            'hepa' => [
                'name' => 'Hepatitis A Vaccine',
                'short_name' => 'HepA',
                'disease_for' => 'Hepatitis A',
                'description' => 'Protects travelers and high-risk groups from Hep A.',
                'side_effects' => 'Soreness, headache',
                'dosage_information' => '1.0 ml',
            ],
            'typhoid' => [
                'name' => 'Typhoid Vaccine (Polysaccharide)',
                'short_name' => 'Typhoid',
                'disease_for' => 'Typhoid Fever',
                'description' => 'Protects travelers going to high-risk zones.',
                'side_effects' => 'Redness at injection site, headache',
                'dosage_information' => '0.5 ml',
            ],
        ];

        $vaccines = [];
        foreach ($records as $key => $record) {
            $vaccines[$key] = Vaccination::updateOrCreate(
                ['name' => $record['name']],
                array_merge($record, [
                    'contraindications' => 'Severe allergic reaction to any component',
                    'precautions' => 'Moderate or severe acute illness with or without fever',
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
            'pregnancy' => ['Maternal Care Plan', VaccinationProgramTargetType::PREGNANCY],
            'child' => ['Child Routine Care', VaccinationProgramTargetType::CHILD],
            'adult' => ['General Adult Protection', VaccinationProgramTargetType::ADULT],
            'elderly' => ['Senior Wellness Plan', VaccinationProgramTargetType::ELDERLY],
            'travel' => ['Travel Immunization', VaccinationProgramTargetType::TRAVEL],
        ];

        $programs = [];
        foreach ($records as $key => [$name, $type]) {
            $programs[$key] = VaccinationProgram::updateOrCreate(
                ['slug' => Str::slug($name)],
                [
                    'name' => $name,
                    'description' => "{$type->label()} program.",
                    'target_type' => $type->value,
                    'is_active' => true,
                ]
            );
        }

        return $programs;
    }

    /**
     * @param array<string, VaccinationProgram> $programs
     * @param array<string, Vaccination> $vaccines
     * @return array<string, VaccinationTemplate>
     */
    private function templates(Doctor $doctor, array $programs, array $vaccines): array
    {
        $templates = [
            'baby_schedule' => [
                'program' => 'baby',
                'name' => 'Standard Baby Birth Schedule',
                'description' => 'Core immunizations given to newborn infants right at birth.',
                'items' => [
                    [
                        'vaccine' => 'bcg',
                        'set' => 'At Birth',
                        'dose' => 1,
                        'age' => 'At Birth',
                        'depends' => false,
                        'months' => 0,
                        'days' => 0,
                        'sort' => 1,
                    ],
                    [
                        'vaccine' => 'hepb',
                        'set' => 'At Birth',
                        'dose' => 1,
                        'age' => 'At Birth',
                        'depends' => false,
                        'months' => 0,
                        'days' => 0,
                        'sort' => 2,
                    ],
                ],
            ],
            'child_schedule' => [
                'program' => 'child',
                'name' => 'Routine Pediatric Plan',
                'description' => 'Standard childhood immunization given at 6 and 10 weeks of age.',
                'items' => [
                    [
                        'vaccine' => 'mmr',
                        'set' => '6 Weeks Phase',
                        'dose' => 1,
                        'age' => '6 Weeks',
                        'depends' => false,
                        'months' => 1,
                        'days' => 14,
                        'sort' => 1,
                    ],
                    [
                        'vaccine' => 'pcv',
                        'set' => '6 Weeks Phase',
                        'dose' => 1,
                        'age' => '6 Weeks',
                        'depends' => false,
                        'months' => 1,
                        'days' => 14,
                        'sort' => 2,
                    ],
                    [
                        'vaccine' => 'pcv',
                        'set' => '10 Weeks Phase',
                        'dose' => 2,
                        'age' => '10 Weeks',
                        'depends' => true,
                        'months' => 1,
                        'days' => 0,
                        'sort' => 3,
                    ],
                ],
            ],
            'pregnancy_schedule' => [
                'program' => 'pregnancy',
                'name' => 'Maternal Vaccination Schedule',
                'description' => 'Vital vaccines administered during pregnancy to protect both mother and baby.',
                'items' => [
                    [
                        'vaccine' => 'flu',
                        'set' => 'Second Trimester',
                        'dose' => 1,
                        'age' => '2nd Trimester (16 Weeks)',
                        'depends' => false,
                        'months' => 16,
                        'days' => 0,
                        'offset_unit' => 'weeks',
                        'sort' => 1,
                    ],
                    [
                        'vaccine' => 'tdap',
                        'set' => 'Third Trimester',
                        'dose' => 1,
                        'age' => '27-36 Weeks (28 Weeks)',
                        'depends' => false,
                        'months' => 28,
                        'days' => 0,
                        'offset_unit' => 'weeks',
                        'sort' => 2,
                    ],
                ],
            ],
            'travel_schedule' => [
                'program' => 'travel',
                'name' => 'Adult International Travel Plan',
                'description' => 'Important protection for travelers heading to endemic regions.',
                'items' => [
                    [
                        'vaccine' => 'hepa',
                        'set' => 'Pre-Departure',
                        'dose' => 1,
                        'age' => 'Immediate',
                        'depends' => false,
                        'months' => 0,
                        'days' => 0,
                        'sort' => 1,
                    ],
                    [
                        'vaccine' => 'typhoid',
                        'set' => 'Pre-Departure',
                        'dose' => 1,
                        'age' => 'Immediate',
                        'depends' => false,
                        'months' => 0,
                        'days' => 0,
                        'sort' => 2,
                    ],
                    [
                        'vaccine' => 'hepa',
                        'set' => 'Post-Travel Booster',
                        'dose' => 2,
                        'age' => '6 Months Later',
                        'depends' => true,
                        'months' => 6,
                        'days' => 0,
                        'sort' => 3,
                    ],
                ],
            ],
            'elderly_schedule' => [
                'program' => 'elderly',
                'name' => 'Senior Shingles & Pneumo Protocol',
                'description' => 'Recommended schedule for adults aged 65 and older.',
                'items' => [
                    [
                        'vaccine' => 'pcv',
                        'set' => 'Initial Assessment',
                        'dose' => 1,
                        'age' => 'Immediate',
                        'depends' => false,
                        'months' => 0,
                        'days' => 0,
                        'sort' => 1,
                    ],
                    [
                        'vaccine' => 'shingles',
                        'set' => 'Initial Assessment',
                        'dose' => 1,
                        'age' => 'Immediate',
                        'depends' => false,
                        'months' => 0,
                        'days' => 0,
                        'sort' => 2,
                    ],
                    [
                        'vaccine' => 'shingles',
                        'set' => 'Follow-up Booster',
                        'dose' => 2,
                        'age' => '2 Months Later',
                        'depends' => true,
                        'months' => 2,
                        'days' => 0,
                        'sort' => 3,
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
                    'reminder_1_days_before' => 7,
                    'reminder_2_days_before' => 3,
                    'reminder_3_days_before' => 1,
                    'overdue_alert_days_after' => 1,
                    'is_active' => true,
                ]
            );

            $template->items()->delete();
            $isPregnancy = $templateData['program'] === 'pregnancy';

            foreach ($templateData['items'] as $index => $item) {
                $minAgeDays = $item['depends'] ? null : ($isPregnancy ? ($item['months'] * 7 + $item['days']) : ($item['months'] * 30 + $item['days']));
                $timingType = $item['depends'] ? 'previous_dose' : 'base_date';
                $offsetUnit = $item['offset_unit'] ?? ($item['months'] > 0 ? 'months' : 'days');
                $offsetValue = $item['depends'] ? 0 : ($offsetUnit === 'days' ? $item['days'] : $item['months']);
                $intervalUnit = $item['months'] > 0 ? 'months' : 'days';
                $intervalValue = $item['depends'] ? ($intervalUnit === 'days' ? $item['days'] : $item['months']) : 0;

                VaccinationTemplateItem::create([
                    'vaccination_template_id' => $template->id,
                    'vaccination_id' => $vaccines[$item['vaccine']]->id,
                    'set_name' => $item['set'],
                    'set_description' => "Seeded {$item['set']} group.",
                    'set_sort_order' => $index + 1,
                    'dose_no' => $item['dose'],
                    'depends_on_previous_dose' => $item['depends'],
                    'interval_days' => $item['depends'] ? $item['days'] : 0,
                    'interval_months' => $item['depends'] ? $item['months'] : 0,
                    'minimum_age_days' => $minAgeDays,
                    'maximum_age_days' => null,
                    'recommended_age_label' => $item['age'],
                    'due_after_months' => $item['depends'] ? 0 : $item['months'],
                    'due_after_days' => $item['depends'] ? 0 : $item['days'],
                    'timing_type' => $timingType,
                    'offset_value' => $offsetValue,
                    'offset_unit' => $offsetUnit,
                    'interval_value' => $intervalValue,
                    'interval_unit' => $intervalUnit,
                    'sort_order' => $item['sort'],
                ]);
            }

            $created[$key] = $template;
        }

        return $created;
    }

    /**
     * @param array<string, VaccinationTemplate> $templates
     */
    private function assignments(Doctor $doctor, Patient $patient, array $templates): void
    {
        $today = now()->startOfDay();

        $assignmentData = [
            [
                'template' => 'baby_schedule',
                'start_date' => $today->copy()->toDateString(),
            ],
            [
                'template' => 'pregnancy_schedule',
                'start_date' => $today->copy()->subWeeks(16)->toDateString(),
            ],
            [
                'template' => 'travel_schedule',
                'start_date' => $today->copy()->subDays(5)->toDateString(),
            ],
            [
                'template' => 'elderly_schedule',
                'start_date' => $today->copy()->toDateString(),
            ],
        ];

        foreach ($assignmentData as $data) {
            $this->seedTemplateDosesForPatient(
                doctor: $doctor,
                patient: $patient,
                template: $templates[$data['template']],
                startDate: \Carbon\Carbon::parse($data['start_date'])->startOfDay()
            );
        }

        // Shape a current-month demo timeline: completed, due today, overdue, on hold, skipped, and future doses.
        foreach ($templates as $template) {
            $doses = PatientVaccination::query()
                ->where('doctor_id', $doctor->id)
                ->where('patient_id', $patient->id)
                ->where('vaccination_template_id', $template->id)
                ->orderBy('set_sort_order')
                ->orderBy('scheduled_date')
                ->orderBy('dose_no')
                ->get();

            $firstDose = $doses->first();

            if ($firstDose) {
                $firstDose->update([
                    'status' => VaccinationStatus::COMPLETED->value,
                    'completed_date' => $today->copy()->subDays(2)->toDateString(),
                    'batch_number' => 'VAC-BATCH-001',
                    'route' => 'Injection',
                    'site' => 'Left thigh',
                    'dose_amount' => $firstDose->vaccination?->dosage_information ?: '0.5 ml',
                    'given_at' => 'Demo Clinic',
                    'given_by' => 'Dr. Vaccination Demo',
                    'doctor_notes' => 'First dose completed successfully during routine visit.',
                    'side_effect_observed' => 'No immediate side effects observed.',
                    'patient_reaction' => 'Normal',
                ]);
            }

            $secondDose = $doses->skip(1)->first();
            if ($secondDose) {
                $secondDose->update([
                    'status' => VaccinationStatus::UPCOMING->value,
                    'due_date' => $today->copy()->toDateString(),
                    'scheduled_date' => $today->copy()->toDateString(),
                    'expected_date' => $today->copy()->toDateString(),
                    'overdue_date' => $today->copy()->addDay()->toDateString(),
                    'missed_date' => $today->copy()->addDays(7)->toDateString(),
                ]);
            }

            $thirdDose = $doses->skip(2)->first();
            if ($thirdDose) {
                $thirdDose->update([
                    'status' => VaccinationStatus::UPCOMING->value,
                    'due_date' => $today->copy()->addWeeks(3)->toDateString(),
                    'scheduled_date' => $today->copy()->addWeeks(3)->toDateString(),
                    'expected_date' => $today->copy()->addWeeks(3)->toDateString(),
                    'overdue_date' => $today->copy()->addWeeks(3)->addDay()->toDateString(),
                    'missed_date' => $today->copy()->addWeeks(4)->toDateString(),
                ]);
            }
        }

        PatientVaccination::query()
            ->where('doctor_id', $doctor->id)
            ->whereHas('vaccination', fn($query) => $query->where('name', 'BCG Vaccine'))
            ->first()
            ?->update([
                'status' => VaccinationStatus::MISSED->value,
                'due_date' => $today->copy()->subWeeks(2)->toDateString(),
                'scheduled_date' => $today->copy()->subWeeks(2)->toDateString(),
                'expected_date' => $today->copy()->subWeeks(2)->toDateString(),
                'overdue_date' => $today->copy()->subWeeks(2)->addDay()->toDateString(),
                'missed_date' => $today->copy()->subWeek()->toDateString(),
                'doctor_notes' => 'Missed during newborn follow-up. Doctor should review before rescheduling.',
            ]);

        PatientVaccination::query()
            ->where('doctor_id', $doctor->id)
            ->whereHas('vaccination', fn($query) => $query->where('name', 'Tdap Vaccine'))
            ->first()
            ?->update([
                'status' => VaccinationStatus::ON_HOLD->value,
                'due_date' => $today->copy()->addWeeks(8)->toDateString(),
                'scheduled_date' => $today->copy()->addWeeks(8)->toDateString(),
                'expected_date' => $today->copy()->addWeeks(8)->toDateString(),
                'on_hold_reason' => 'Awaiting pregnancy checkup before confirming the dose date.',
            ]);

        PatientVaccination::query()
            ->where('doctor_id', $doctor->id)
            ->whereHas('vaccination', fn($query) => $query->where('name', 'Typhoid Vaccine (Polysaccharide)'))
            ->first()
            ?->update([
                'status' => VaccinationStatus::SKIPPED_BY_DOCTOR->value,
                'due_date' => $today->copy()->toDateString(),
                'scheduled_date' => $today->copy()->toDateString(),
                'expected_date' => $today->copy()->toDateString(),
                'skipped_reason' => 'Skipped for demo testing after doctor review.',
            ]);
    }

    private function seedTemplateDosesForPatient(Doctor $doctor, Patient $patient, VaccinationTemplate $template, \Carbon\Carbon $startDate): void
    {
        $template->loadMissing(['items.vaccination', 'program']);

        $ageBaseDate = $startDate->copy();

        $previousDate = $startDate->copy();

        foreach ($template->items as $index => $item) {
            $timingType = $item->effectiveTimingType();
            $scheduledDate = null;

            if ($timingType !== 'doctor_manual_date') {
                $baseDate = $timingType === 'previous_dose' ? $previousDate : $ageBaseDate;
                $value = $timingType === 'previous_dose'
                    ? $item->effectiveIntervalValue()
                    : $item->effectiveOffsetValue();
                $unit = $timingType === 'previous_dose'
                    ? $item->effectiveIntervalUnit()
                    : $item->effectiveOffsetUnit();

                $scheduledDate = $this->addValueUnitToDate($baseDate, $value, $unit);
                $previousDate = $scheduledDate->copy()->startOfDay();
            }

            PatientVaccination::updateOrCreate([
                'patient_id' => $patient->id,
                'doctor_id' => $doctor->id,
                'vaccination_id' => $item->vaccination_id,
                'vaccination_template_id' => $template->id,
                'dose_no' => $item->dose_no ?? ($index + 1),
                'set_name' => $item->set_name,
            ], [
                'patient_vaccination_program_id' => null,
                'set_name' => $item->set_name,
                'set_sort_order' => $item->set_sort_order ?? ($index + 1),
                'recommended_age_label' => $item->recommended_age_label,
                'dose_no' => $item->dose_no ?? ($index + 1),
                'first_dose_date' => $startDate->toDateString(),
                'due_after_days' => $item->due_after_days ?? 0,
                'due_after_months' => $item->due_after_months ?? 0,
                'expected_date' => $scheduledDate?->toDateString(),
                'assigned_date' => $startDate->toDateString(),
                'due_date' => $scheduledDate?->toDateString(),
                'scheduled_date' => $scheduledDate?->toDateString(),
                'grace_period_before_days' => $item->grace_period_before_days ?? 0,
                'grace_period_after_days' => $item->grace_period_after_days ?? 0,
                'missed_date' => $scheduledDate?->copy()->addDays($item->grace_period_after_days ?? 0)->toDateString(),
                'overdue_date' => $scheduledDate?->copy()->addDay()->toDateString(),
                'status' => VaccinationStatus::UPCOMING,
                'manufacturer' => $item->vaccination?->manufacturer,
                'reminder_sent' => false,
                'next_reminder_at' => ($scheduledDate && $scheduledDate->copy()->subDay()->greaterThanOrEqualTo(\Carbon\Carbon::create(1970, 1, 2)))
                    ? $scheduledDate->copy()->subDay()
                    : null,
            ]);
        }
    }

    private function addValueUnitToDate(\Carbon\Carbon $date, int $value, string $unit): \Carbon\Carbon
    {
        return match ($unit) {
            'weeks' => $date->copy()->addWeeks($value),
            'months' => $date->copy()->addMonths($value),
            'years' => $date->copy()->addYears($value),
            default => $date->copy()->addDays($value),
        };
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
