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

        $this->command->info("Creating 5 test appointments...");

        for ($i = 0; $i < 5; $i++) {
            $doctor = $doctors->random();
            $patient = $patients->random();
            $availability = DoctorAvailability::where('doctor_id', $doctor->id)->inRandomOrder()->first();

            if (!$availability) continue;

            // First 2 are past completed appointments
            if ($i < 2) {
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
                'notes' => ['Test appointment ' . ($i + 1)],
                'slug' => implode('-', array_filter([
                    Str::slug($doctor->first_name . '-' . $doctor->last_name),
                    $date instanceof \Carbon\Carbon ? $date->format('Y-m-d') : (is_string($date) ? \Carbon\Carbon::parse($date)->format('Y-m-d') : null),
                    Str::lower(Str::random(3)),
                ])),
            ]);

            $this->createPayment($appointment, 1, 'paid');

            // Add medical report and prescription for completed or confirmed appointments
            if (in_array($status, [AppointmentStatus::COMPLETED->value, AppointmentStatus::CONFIRMED->value])) {
                $prescData = $prescriptionData[array_rand($prescriptionData)];
                $this->createPrescription($appointment, $prescData, 0);
                PrescriptionService::generatePdf($appointment->id);

                $reportData = $reportTypes[array_rand($reportTypes)];
                $this->createMedicalReport($appointment, $reportData, Carbon::parse($date));
            }
        }

        $this->command->info('5 Appointments seeded successfully!');
    }

    // Removal of createAvailability method as requested.
    // Централизация создания слотов в DoctorAvailabilitySeeder.

    /**
     * Create a payment record
     */
    private function createPayment(Appointment $appointment, float $amount, string $status = 'paid'): void
    {
        $paymentMethods = ['card', 'netbanking', 'wallet', 'upi'];

        Payment::create([
            'appointment_id' => $appointment->id,
            'amount' => 1, // HARD CODE PAYMENT AMOUNT TO 1
            'fee' => 0,
            'payment_method' => $paymentMethods[array_rand($paymentMethods)],
            'status' => $status,
            'transaction_id' => 'txn_' . Str::random(14),
            'razorpay_order_id' => 'order_' . Str::random(14),
            'razorpay_payment_id' => 'pay_' . Str::random(14),
        ]);
    }

    /**
     * Create a prescription record
     */
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

        // PDF generation is now handled after the loop for efficiency
        // PrescriptionService::generatePdf($appointment->id);
    }

    /**
     * Create a medical report record
     */
    private function createMedicalReport(Appointment $appointment, array $data, Carbon $date): void
    {
        $isShared = ($appointment->doctor_id && $appointment->id) ? true : false;

        $report = MedicalReport::create([
            'appointment_id' => $isShared ? $appointment->id : null,
            'patient_id' => $isShared ? $appointment->patient_id : null,
            'doctor_id' => $isShared ? $appointment->doctor_id : null,
            'name' => $data['name'], // fixed from 'title' to 'name'
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

        // Attach a file (mix of PDF and images)
        $fileUrl = rand(0, 1) === 0 ? $pdfUrl : $imageUrls[array_rand($imageUrls)];

        ModuleDocument::create([
            'model_type' => MedicalReport::class,
            'model_id' => $report->id,
            'name' => 'file',
            'files' => [$fileUrl],
        ]);
    }
}
