<?php

namespace Database\Seeders;

use App\Models\{Appointment, Doctor, DoctorAvailability, MedicalReport, ModuleDocument, Patient, Payment, Prescription};
use App\Enums\{MedicalReportStatus, AppointmentStatus};
use App\Services\PrescriptionService;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class AppointmentSeeder extends Seeder
{
    /**
     * @var array<int, array{label: string, consultation_type: string, opd_type: string|null, completed: bool}>
     */
    private const APPOINTMENT_SPECS = [
        ['label' => 'Video consultation 1', 'consultation_type' => 'video', 'opd_type' => null, 'completed' => true],
        ['label' => 'Video consultation 2', 'consultation_type' => 'video', 'opd_type' => null, 'completed' => false],
        ['label' => 'General OPD 1', 'consultation_type' => 'in-person', 'opd_type' => 'general', 'completed' => true],
        ['label' => 'General OPD 2', 'consultation_type' => 'in-person', 'opd_type' => 'general', 'completed' => false],
        ['label' => 'Private OPD 1', 'consultation_type' => 'in-person', 'opd_type' => 'private', 'completed' => true],
        ['label' => 'Private OPD 2', 'consultation_type' => 'in-person', 'opd_type' => 'private', 'completed' => false],
    ];

    /**
     * @var array<int, array{label: string, consultation_type: string, opd_type: string|null, status: string, queue_status: string}>
     */
    private const TODAY_APPOINTMENT_SPECS = [
        ['label' => 'Today Video Completed', 'consultation_type' => 'video', 'opd_type' => null, 'status' => 'completed', 'queue_status' => 'completed'],
        ['label' => 'Today In-Person Running', 'consultation_type' => 'in-person', 'opd_type' => 'general', 'status' => 'confirmed', 'queue_status' => 'started'],
        ['label' => 'Today In-Person Waiting 1', 'consultation_type' => 'in-person', 'opd_type' => 'general', 'status' => 'confirmed', 'queue_status' => 'checkin'],
        ['label' => 'Today In-Person Waiting 2', 'consultation_type' => 'in-person', 'opd_type' => 'general', 'status' => 'confirmed', 'queue_status' => 'checkin'],
        ['label' => 'Today In-Person Skipped', 'consultation_type' => 'in-person', 'opd_type' => 'general', 'status' => 'confirmed', 'queue_status' => 'skipped'],
    ];

    public function run(): void
    {
        $doctors = Doctor::with('user')->get();
        $patients = Patient::where('source', 'app')->where('create_user_account', true)->get();

        if ($doctors->isEmpty() || $patients->isEmpty()) {
            $this->command->warn('No doctors or patients found.');
            return;
        }

        $generalDoctors = $doctors->filter(fn (Doctor $doctor) => !$this->isTargetQueueDoctor($doctor));
        if ($generalDoctors->isEmpty()) {
            $generalDoctors = $doctors;
        }

        $prescriptionData = [
            ['name' => 'Aspirin', 'type' => 'Oral Pill', 'dosage' => '81mg', 'frequency' => '1 capsule, 3 times a day', 'times' => ['07:00', '12:00', '18:00']],
            ['name' => 'Paracetamol', 'type' => 'Tablet', 'dosage' => '500mg', 'frequency' => '1 tablet, twice a day', 'times' => ['08:00', '20:00']],
        ];

        $reportTypes = [
            ['name' => 'Blood Test Results', 'type' => 'lab_report'],
            ['name' => 'X-Ray Analysis', 'type' => 'radiology'],
        ];

        $this->command->info('Creating test appointments (2 video, 2 general OPD, 2 private OPD)...');

        $created = 0;

        foreach (self::APPOINTMENT_SPECS as $spec) {
            $doctor = $generalDoctors->random();
            $patient = $patients->random();
            $availability = $this->resolveAvailability(
                $doctor,
                $spec['consultation_type'],
                $spec['opd_type'],
            );

            if (!$availability) {
                $this->command->warn("Skipping {$spec['label']}: no matching availability found.");
                continue;
            }

            if ($spec['completed']) {
                $date = Carbon::today()->subDays(rand(1, 15));
                $status = AppointmentStatus::COMPLETED->value;
            } else {
                $date = $availability->date ?? Carbon::today()->next($availability->day_of_week ?? Carbon::MONDAY);
                $status = AppointmentStatus::CONFIRMED->value;
            }

            $appointment = Appointment::create([
                'id' => Str::uuid(),
                'patient_id' => $patient->id,
                'doctor_id' => $doctor->id,
                'availability_id' => $availability->id,
                'appointment_date' => $date,
                'appointment_time' => $availability->start_time,
                'appointment_end_time' => $availability->end_time,
                'consultation_type' => $availability->consultation_type,
                'status' => $status,
                'fee_amount' => 1,
                'visit_reason' => [$spec['label']],
                'slug' => implode('-', array_filter([
                    Str::slug($doctor->first_name . '-' . $doctor->last_name),
                    $date instanceof Carbon ? $date->format('Y-m-d') : Carbon::parse($date)->format('Y-m-d'),
                    Str::lower(Str::random(3)),
                ])),
            ]);

            $appointment->assignQueueNumber();

            $this->createPayment($appointment, 1, 'paid');

            if (in_array($status, [AppointmentStatus::COMPLETED->value, AppointmentStatus::CONFIRMED->value], true)) {
                $prescData = $prescriptionData[array_rand($prescriptionData)];
                $this->createPrescription($appointment, $prescData, 0);
                PrescriptionService::generatePdf($appointment->id);

                $reportData = $reportTypes[array_rand($reportTypes)];
                $this->createMedicalReport($appointment, $reportData, Carbon::parse($date));
            }

            $created++;
        }

        foreach (self::TODAY_APPOINTMENT_SPECS as $spec) {
            $doctor = $generalDoctors->random();
            $patient = $patients->random();
            $availability = $this->resolveAvailability(
                $doctor,
                $spec['consultation_type'],
                $spec['opd_type'],
                Carbon::today()
            );

            if (!$availability) {
                $this->command->warn("Skipping today spec {$spec['label']}: no availability resolved.");
                continue;
            }

            $appointment = Appointment::create([
                'id' => Str::uuid(),
                'patient_id' => $patient->id,
                'doctor_id' => $doctor->id,
                'availability_id' => $availability->id,
                'appointment_date' => Carbon::today(),
                'appointment_time' => $availability->start_time,
                'appointment_end_time' => $availability->end_time,
                'consultation_type' => $availability->consultation_type,
                'status' => $spec['status'] === 'completed' ? AppointmentStatus::COMPLETED->value : AppointmentStatus::CONFIRMED->value,
                'queue_status' => $spec['queue_status'],
                'fee_amount' => 1,
                'visit_reason' => [$spec['label']],
                'slug' => implode('-', array_filter([
                    Str::slug($doctor->first_name . '-' . $doctor->last_name),
                    'today',
                    Str::lower(Str::random(3)),
                ])),
            ]);

            $appointment->assignQueueNumber();

            $this->createPayment($appointment, 1, 'paid');

            if ($spec['status'] === 'completed') {
                $prescData = $prescriptionData[array_rand($prescriptionData)];
                $this->createPrescription($appointment, $prescData, 0);
                PrescriptionService::generatePdf($appointment->id);

                $reportData = $reportTypes[array_rand($reportTypes)];
                $this->createMedicalReport($appointment, $reportData, Carbon::today());
            }

            $created++;
        }

        $targetDoctors = $doctors
            ->filter(fn (Doctor $doctor) => $this->isTargetQueueDoctor($doctor))
            ->values();

        $created += $this->seedDisplayQueueScenarios($doctors, $patients);
        $created += $this->seedTodayLoadForEachDoctor(
            $targetDoctors->isNotEmpty() ? $targetDoctors : $doctors,
            $patients
        );

        $this->command->info("{$created} appointments seeded successfully!");
    }

    private function seedTodayLoadForEachDoctor($doctors, $patients): int
    {
        if ($doctors->isEmpty() || $patients->isEmpty()) {
            return 0;
        }

        $today = Carbon::today();
        $totalPatients = max(1, $patients->count());
        $created = 0;
        $roundedNow = Carbon::now()->copy()->seconds(0);
        $minuteRemainder = $roundedNow->minute % 15;
        if ($minuteRemainder !== 0) {
            $roundedNow->addMinutes(15 - $minuteRemainder);
        }
        $queueEnd = $today->copy()->setTime(20, 0);

        foreach ($doctors->values() as $doctorIndex => $doctor) {
            $room = $doctor->address_line2 ?: 'Room ' . str_pad((string) (101 + ($doctorIndex % 20)), 3, '0', STR_PAD_LEFT);
            $availability = $this->upsertScenarioAvailability($doctor, '09:00', '18:00', 'general', $room);
            $slotCursor = $roundedNow->copy();
            if ($slotCursor->greaterThanOrEqualTo($queueEnd)) {
                $slotCursor = $queueEnd->copy()->subHours(2);
            }

            $slotStarts = [];
            while ($slotCursor->lt($queueEnd)) {
                $slotStarts[] = $slotCursor->copy();
                $slotCursor->addMinutes(15);
            }

            $slotCount = count($slotStarts);
            if ($slotCount === 0) {
                continue;
            }

            $isTarget = $this->isTargetQueueDoctor($doctor);
            $completedCount = $isTarget ? 0 : min(4, max(1, min($slotCount, 4)));
            $skippedIndex = ($isTarget || $slotCount <= $completedCount) ? null : $completedCount;

            foreach ($slotStarts as $slotIndex => $startAt) {
                $patient = $patients[$slotIndex % $totalPatients];
                $endAt = $startAt->copy()->addMinutes(15);
                $slug = 'opd-load-' . $doctor->id . '-' . $today->format('Ymd') . '-' . str_pad((string) ($slotIndex + 1), 2, '0', STR_PAD_LEFT);
                $queueStatus = $slotIndex < $completedCount
                    ? 'completed'
                    : ($skippedIndex !== null && $slotIndex === $skippedIndex ? 'skipped' : 'checkin');
                $appointmentStatus = $queueStatus === 'completed'
                    ? AppointmentStatus::COMPLETED->value
                    : AppointmentStatus::CONFIRMED->value;

                $appointment = Appointment::firstOrNew(['slug' => $slug]);
                if (! $appointment->exists) {
                    $appointment->id = (string) Str::uuid();
                }

                $appointment->fill([
                    'patient_id' => $patient->id,
                    'doctor_id' => $doctor->id,
                    'availability_id' => $availability->id,
                    'appointment_date' => $today,
                    'appointment_time' => $startAt->format('H:i:s'),
                    'appointment_end_time' => $endAt->format('H:i:s'),
                    'consultation_type' => 'in-person',
                    'status' => $appointmentStatus,
                    'queue_status' => $queueStatus,
                    'fee_amount' => 1,
                    'visit_reason' => ['OPD Queue Seeder'],
                ]);
                $appointment->save();

                if (blank($appointment->queue_number)) {
                    $appointment->assignQueueNumber();
                }

                $this->createPaymentIfMissing($appointment);

                if ($queueStatus === 'completed' && ! $appointment->prescriptions()->exists()) {
                    $this->createPrescription($appointment, [
                        'name' => 'Paracetamol',
                        'type' => 'Tablet',
                        'dosage' => '500mg',
                        'frequency' => '1 tablet, twice a day',
                        'times' => ['08:00', '20:00'],
                    ], 0);
                }

                $created++;
            }
        }

        return $created;
    }

    private function seedDisplayQueueScenarios($doctors, $patients): int
    {
        $scenarioDoctors = $doctors->filter(fn (Doctor $doctor) => !$this->isTargetQueueDoctor($doctor))->take(4)->values();
        $scenarioPatients = $patients->take(12)->values();

        if ($scenarioDoctors->count() < 4 || $scenarioPatients->count() < 8) {
            return 0;
        }

        $availabilities = [
            0 => $this->upsertScenarioAvailability($scenarioDoctors[0], '09:00', '12:00', 'general', 'Room 204'),
            1 => $this->upsertScenarioAvailability($scenarioDoctors[1], '09:30', '12:30', 'general', 'Room 108'),
            2 => $this->upsertScenarioAvailability($scenarioDoctors[2], '10:00', '13:00', 'general', 'Room 310'),
            3 => $this->upsertScenarioAvailability($scenarioDoctors[3], '10:30', '13:30', 'general', 'Room 112'),
        ];

        $scenarios = [
            ['doctor' => 0, 'patient' => 0, 'queue_status' => 'started', 'status' => AppointmentStatus::CONFIRMED->value, 'suffix' => 'current-1'],
            ['doctor' => 0, 'patient' => 1, 'queue_status' => 'checkin', 'status' => AppointmentStatus::CONFIRMED->value, 'suffix' => 'waiting-1'],
            ['doctor' => 0, 'patient' => 2, 'queue_status' => 'checkin', 'status' => AppointmentStatus::CONFIRMED->value, 'suffix' => 'waiting-2'],
            ['doctor' => 1, 'patient' => 3, 'queue_status' => 'started', 'status' => AppointmentStatus::CONFIRMED->value, 'suffix' => 'current-1'],
            ['doctor' => 1, 'patient' => 4, 'queue_status' => 'checkin', 'status' => AppointmentStatus::CONFIRMED->value, 'suffix' => 'waiting-1'],
            ['doctor' => 2, 'patient' => 5, 'queue_status' => 'checkin', 'status' => AppointmentStatus::CONFIRMED->value, 'suffix' => 'waiting-1'],
            ['doctor' => 2, 'patient' => 6, 'queue_status' => 'checkin', 'status' => AppointmentStatus::CONFIRMED->value, 'suffix' => 'waiting-2'],
            ['doctor' => 3, 'patient' => 7, 'queue_status' => 'started', 'status' => AppointmentStatus::CONFIRMED->value, 'suffix' => 'current-1'],
            ['doctor' => 3, 'patient' => 8, 'queue_status' => 'skipped', 'status' => AppointmentStatus::CONFIRMED->value, 'suffix' => 'skipped-1'],
            ['doctor' => 3, 'patient' => 9, 'queue_status' => 'completed', 'status' => AppointmentStatus::COMPLETED->value, 'suffix' => 'completed-1'],
        ];

        $created = 0;

        foreach ($scenarios as $scenario) {
            $doctor = $scenarioDoctors[$scenario['doctor']];
            $patient = $scenarioPatients[$scenario['patient']];
            $availability = $availabilities[$scenario['doctor']];
            $slug = Str::slug($doctor->first_name . '-' . $doctor->last_name) . '-display-' . $scenario['suffix'];

            $appointment = Appointment::firstOrNew(['slug' => $slug]);

            if (! $appointment->exists) {
                $appointment->id = (string) Str::uuid();
            }

            $appointment->fill([
                'patient_id' => $patient->id,
                'doctor_id' => $doctor->id,
                'availability_id' => $availability->id,
                'appointment_date' => Carbon::today(),
                'appointment_time' => $availability->start_time,
                'appointment_end_time' => $availability->end_time,
                'consultation_type' => $availability->consultation_type,
                'status' => $scenario['status'],
                'queue_status' => $scenario['queue_status'],
                'fee_amount' => 1,
                'visit_reason' => ['Display Scenario Queue'],
            ]);
            $appointment->save();

            if (blank($appointment->queue_number)) {
                $appointment->assignQueueNumber();
            }

            $this->createPaymentIfMissing($appointment);
            $created++;
        }

        return $created;
    }

    private function resolveAvailability(Doctor $doctor, string $consultationType, ?string $opdType, ?Carbon $date = null): ?DoctorAvailability
    {
        $availability = $this->findAvailability($consultationType, $opdType, $date);

        if ($availability) {
            return $availability;
        }

        return $this->createAvailability($doctor, $consultationType, $opdType, $date);
    }

    private function findAvailability(string $consultationType, ?string $opdType, ?Carbon $date = null): ?DoctorAvailability
    {
        $query = DoctorAvailability::query()
            ->where('consultation_type', $consultationType)
            ->where('is_available', true);

        if ($date) {
            $query->where(function ($q) use ($date) {
                $q->whereDate('date', $date)
                    ->orWhere(function ($sub) use ($date) {
                        $sub->whereNull('date')
                            ->where('is_recurring', true)
                            ->whereDate('recurring_start_date', '<=', $date)
                            ->whereDate('recurring_end_date', '>=', $date);
                    });
            });
        }

        if ($consultationType === 'in-person' && $opdType) {
            $query->where('opd_type', $opdType);
        }

        return $query->inRandomOrder()->first();
    }

    private function createAvailability(Doctor $doctor, string $consultationType, ?string $opdType, ?Carbon $date = null): DoctorAvailability
    {
        $isVideo = $consultationType === 'video';
        $targetDate = $date ?? Carbon::tomorrow();

        return DoctorAvailability::create([
            'doctor_id' => $doctor->id,
            'date' => $isVideo ? null : $targetDate->format('Y-m-d'),
            'day_of_week' => $isVideo ? null : strtolower($targetDate->format('l')),
            'start_time' => $isVideo ? '09:00' : '14:00',
            'end_time' => $isVideo ? '12:00' : '16:00',
            'capacity' => 10,
            'consultation_type' => $consultationType,
            'is_recurring' => $isVideo,
            'opd_type' => $isVideo ? null : $opdType,
            'consultation_fee' => 1,
            'is_available' => true,
            'recurring_start_date' => $isVideo ? Carbon::today()->format('Y-m-d') : null,
            'recurring_end_date' => $isVideo ? Carbon::today()->addMonths(3)->format('Y-m-d') : null,
        ]);
    }

    private function upsertScenarioAvailability(Doctor $doctor, string $startTime, string $endTime, string $opdType, string $room): DoctorAvailability
    {
        $today = Carbon::today()->format('Y-m-d');

        // Reuse existing in-person availability for today to avoid overlap validation failures.
        $existingToday = DoctorAvailability::query()
            ->where('doctor_id', $doctor->id)
            ->whereDate('date', $today)
            ->where('consultation_type', 'in-person')
            ->where('is_available', true)
            ->orderBy('start_time')
            ->first();

        if ($existingToday) {
            $needsSave = false;

            if (blank($existingToday->doctor_room) && !blank($room)) {
                $existingToday->doctor_room = $room;
                $needsSave = true;
            }

            if ((int) ($existingToday->capacity ?? 0) < 20) {
                $existingToday->capacity = 20;
                $needsSave = true;
            }

            if ((string) $existingToday->start_time > $startTime) {
                $existingToday->start_time = $startTime;
                $needsSave = true;
            }

            if ((string) $existingToday->end_time < $endTime) {
                $existingToday->end_time = $endTime;
                $needsSave = true;
            }

            if ($needsSave) {
                $existingToday->save();
            }

            return $existingToday;
        }

        $availability = DoctorAvailability::firstOrNew([
            'doctor_id' => $doctor->id,
            'date' => $today,
            'start_time' => $startTime,
            'end_time' => $endTime,
            'consultation_type' => 'in-person',
            'opd_type' => $opdType,
        ]);

        $availability->fill([
            'capacity' => 20,
            'is_available' => true,
            'is_recurring' => false,
            'day_of_week' => strtolower(Carbon::today()->format('l')),
            'consultation_fee' => 1,
            'doctor_room' => $room,
        ]);
        $availability->save();

        return $availability;
    }

    private function createPayment(Appointment $appointment, float $amount, string $status = 'paid'): void
    {
        $paymentMethods = ['card', 'netbanking', 'wallet', 'upi'];

        Payment::create([
            'appointment_id' => $appointment->id,
            'amount' => 1,
            'fee' => 0,
            'payment_method' => $paymentMethods[array_rand($paymentMethods)],
            'status' => $status,
            'transaction_id' => 'txn_' . Str::random(14),
            'razorpay_order_id' => 'order_' . Str::random(14),
            'razorpay_payment_id' => 'pay_' . Str::random(14),
        ]);
    }

    private function createPaymentIfMissing(Appointment $appointment): void
    {
        if ($appointment->payment()->exists()) {
            return;
        }

        $this->createPayment($appointment, 1, 'paid');
    }

    private function createPrescription(Appointment $appointment, array $data, int $order): void
    {
        Prescription::create([
            'appointment_id' => $appointment->id,
            'doctor_id' => $appointment->doctor_id,
            'patient_id' => $appointment->patient_id,
            'medicine_name' => $data['name'],
            'medicine_type' => $data['type'],
            'dosage' => $data['dosage'],
            'frequency' => $data['frequency'],
            'frequency_times' => $data['times'],
            'duration_type' => rand(0, 1) ? 'days' : 'ongoing',
            'duration_value' => rand(0, 1) ? rand(5, 14) : null,
            'is_ongoing' => rand(0, 1),
            'instructions' => 'Take after meals',
            'start_date' => $appointment->appointment_date,
            'end_date' => Carbon::parse($appointment->appointment_date)->addDays(5),
            'order' => $order,
        ]);
    }

    private function createMedicalReport(Appointment $appointment, array $data, Carbon $date): void
    {
        $isShared = ($appointment->doctor_id && $appointment->id) ? true : false;

        $report = MedicalReport::create([
            'appointment_id' => $isShared ? $appointment->id : null,
            'patient_id' => $isShared ? $appointment->patient_id : null,
            'doctor_id' => $isShared ? $appointment->doctor_id : null,
            'name' => $data['name'],
            'type' => $data['type'],
            'description' => 'Test results for ' . $data['name'],
            'report_date' => $date->copy()->subDays(rand(1, 5)),
            'status' => $isShared ? MedicalReportStatus::SHARED : MedicalReportStatus::UPLOADED,
            'is_shared' => $isShared,
            'results' => [
                'summary' => 'All values within normal range',
                'notes' => 'No abnormalities detected',
            ],
        ]);

        $pdfUrl = 'https://www.w3.org/WAI/ER/tests/xhtml/testfiles/resources/pdf/dummy.pdf';
        $imageUrls = [
            'https://raw.githubusercontent.com/mantinedev/mantine/master/.demo/images/bg-1.png',
            'https://raw.githubusercontent.com/mantinedev/mantine/master/.demo/images/bg-2.jpg',
            'https://raw.githubusercontent.com/mantinedev/mantine/master/.demo/images/bg-3.webp',
        ];

        $fileUrl = rand(0, 1) === 0 ? $pdfUrl : $imageUrls[array_rand($imageUrls)];

        ModuleDocument::create([
            'model_type' => MedicalReport::class,
            'model_id' => $report->id,
            'name' => 'file',
            'files' => [$fileUrl],
        ]);
    }

    private function isTargetQueueDoctor(Doctor $doctor): bool
    {
        $email = strtolower((string) ($doctor->user?->email ?? ''));

        return in_array($email, ['mjoseph@gmail.com', 'kjoseph@gmail.com'], true);
    }
}
