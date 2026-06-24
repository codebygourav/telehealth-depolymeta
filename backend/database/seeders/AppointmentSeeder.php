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
        ['label' => 'Today In-Person Running', 'consultation_type' => 'in-person', 'opd_type' => 'general', 'status' => 'confirmed', 'queue_status' => 'running'],
        ['label' => 'Today In-Person Waiting 1', 'consultation_type' => 'in-person', 'opd_type' => 'general', 'status' => 'confirmed', 'queue_status' => 'waiting'],
        ['label' => 'Today In-Person Waiting 2', 'consultation_type' => 'in-person', 'opd_type' => 'general', 'status' => 'confirmed', 'queue_status' => 'waiting'],
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
            $doctor = $doctors->random();
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
            $doctor = $doctors->random();
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

        $this->command->info("{$created} appointments seeded successfully!");
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
}
