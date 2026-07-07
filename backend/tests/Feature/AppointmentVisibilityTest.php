<?php

namespace Tests\Feature;

use App\Models\{Appointment, Doctor, Patient, User};
use App\Enums\AppointmentStatus;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AppointmentVisibilityTest extends TestCase
{
    use RefreshDatabase;

    protected User $doctorUser;
    protected Doctor $doctor;
    protected User $patientUser;
    protected Patient $patient;

    protected function setUp(): void
    {
        parent::setUp();

        // 1. Set simulated current time
        Carbon::setTestNow('2026-06-26 10:30:00');

        // 2. Ensure roles exist
        Role::firstOrCreate(['name' => 'doctor', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'patient', 'guard_name' => 'web']);

        // 3. Create Doctor User and Doctor Profile
        $this->doctorUser = User::create([
            'name' => 'Test Owner',
            'email' => 'dr.test.owner@telehealth.test',
            'phone' => '9800000001',
            'password' => bcrypt('password'),
            'status' => 'active',
        ]);
        $this->doctorUser->assignRole('doctor');

        $this->doctor = Doctor::create([
            'user_id' => $this->doctorUser->id,
            'first_name' => 'Test',
            'last_name' => 'Owner',
            'gender' => 'male',
            'dob' => '1985-05-05',
            'marital_status' => 'married',
            'blood_group' => 'A+',
            'medical_license_number' => 'MD-TEST-123',
            'years_experience' => 10,
            'bio' => 'Test doctor bio',
            'description' => 'Test doctor description',
            'address_line1' => 'Test Address 1',
            'country' => 'India',
            'state' => 'Maharashtra',
            'city' => 'Mumbai',
            'pincode' => '400001',
            'languages_known' => 'english',
            'status' => 'active',
        ]);

        // 4. Create Patient User and Patient Profile (Non-App patient, e.g. source => web, create_user_account => false)
        $this->patientUser = User::create([
            'name' => 'Test Patient',
            'email' => 'patient.test@example.com',
            'phone' => '9110000001',
            'password' => bcrypt('password'),
            'status' => 'registered',
        ]);
        $this->patientUser->assignRole('patient');

        $this->patient = Patient::create([
            'user_id' => $this->patientUser->id,
            'first_name' => 'Test',
            'last_name' => 'Patient',
            'gender' => 'male',
            'date_of_birth' => '1990-01-01',
            'age' => 36,
            'mobile_no' => '9999912345',
            'email' => 'patient.test@example.com',
            'address' => 'Sample Address',
            'pincode' => '400002',
            'city' => 'Mumbai',
            'state' => 'Maharashtra',
            'nationality' => 'Indian',
            'marital_status' => 'single',
            'blood_group' => 'O+',
            'is_existing_patient' => false,
            'source' => 'website',                 // Non-app source
            'create_user_account' => false,    // No user account created via app flow
        ]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow(null);
        parent::tearDown();
    }

    public function test_patient_and_doctor_my_appointments_filters_work_correctly(): void
    {
        // Create 2 appointments:
        // 1. Passed today (End time 10:00:00 is before current time 10:30:00)
        $appointmentPassed = Appointment::create([
            'patient_id' => $this->patient->id,
            'doctor_id' => $this->doctor->id,
            'appointment_date' => '2026-06-26',
            'appointment_time' => '09:00:00',
            'appointment_end_time' => '10:00:00',
            'status' => AppointmentStatus::CONFIRMED->value,
            'consultation_type' => 'video',
        ]);

        // 2. Future today (End time 12:00:00 is after current time 10:30:00)
        $appointmentFuture = Appointment::create([
            'patient_id' => $this->patient->id,
            'doctor_id' => $this->doctor->id,
            'appointment_date' => '2026-06-26',
            'appointment_time' => '11:00:00',
            'appointment_end_time' => '12:00:00',
            'status' => AppointmentStatus::CONFIRMED->value,
            'consultation_type' => 'video',
        ]);

        // --- PATIENT SIDE TESTS ---
        // 1. Today filter: should only return $appointmentFuture
        $responseToday = $this->actingAs($this->patientUser, 'sanctum')
            ->getJson('/api/v2/appointments/my?filter=today');
        $responseToday->assertStatus(200);
        $this->assertTrue(collect($responseToday->json('data'))->contains('appointment_id', $appointmentFuture->id));
        $this->assertFalse(collect($responseToday->json('data'))->contains('appointment_id', $appointmentPassed->id));

        // 2. Past filter: should only return $appointmentPassed
        $responsePast = $this->actingAs($this->patientUser, 'sanctum')
            ->getJson('/api/v2/appointments/my?filter=past');
        $responsePast->assertStatus(200);
        $this->assertTrue(collect($responsePast->json('data'))->contains('appointment_id', $appointmentPassed->id));
        $this->assertFalse(collect($responsePast->json('data'))->contains('appointment_id', $appointmentFuture->id));

        // 3. Upcoming filter: should return $appointmentFuture
        $responseUpcoming = $this->actingAs($this->patientUser, 'sanctum')
            ->getJson('/api/v2/appointments/my?filter=upcoming');
        $responseUpcoming->assertStatus(200);
        $this->assertTrue(collect($responseUpcoming->json('data'))->contains('appointment_id', $appointmentFuture->id));
        $this->assertFalse(collect($responseUpcoming->json('data'))->contains('appointment_id', $appointmentPassed->id));

        // --- DOCTOR SIDE TESTS ---
        // Doctor should see these appointments as well (even though patient is from source => 'web')
        // 4. Today filter
        $responseDocToday = $this->actingAs($this->doctorUser, 'sanctum')
            ->getJson('/api/v2/appointments/my?filter=today');
        $responseDocToday->assertStatus(200);
        $this->assertTrue(collect($responseDocToday->json('data'))->contains('appointment_id', $appointmentFuture->id));
        $this->assertFalse(collect($responseDocToday->json('data'))->contains('appointment_id', $appointmentPassed->id));

        // 5. Past filter
        $responseDocPast = $this->actingAs($this->doctorUser, 'sanctum')
            ->getJson('/api/v2/appointments/my?filter=past');
        $responseDocPast->assertStatus(200);
        $this->assertTrue(collect($responseDocPast->json('data'))->contains('appointment_id', $appointmentPassed->id));
        $this->assertFalse(collect($responseDocPast->json('data'))->contains('appointment_id', $appointmentFuture->id));
    }

    public function test_doctor_home_and_details_endpoints_work_with_non_app_patients(): void
    {
        // Create a today future appointment
        $appointment = Appointment::create([
            'patient_id' => $this->patient->id,
            'doctor_id' => $this->doctor->id,
            'appointment_date' => '2026-06-26',
            'appointment_time' => '11:00:00',
            'appointment_end_time' => '12:00:00',
            'status' => AppointmentStatus::CONFIRMED->value,
            'consultation_type' => 'video',
        ]);

        // 1. Doctor Home Endpoint: GET /api/v2/doctor/home
        $responseHome = $this->actingAs($this->doctorUser, 'sanctum')
            ->getJson('/api/v2/doctor/home');
        $responseHome->assertStatus(200);
        $responseHome->assertJsonPath('data.summary.todays_appointments', 1);

        // 2. Doctor All Patients/Appointments Endpoint: GET /api/v2/doctor/all-patients
        $responseAllPatients = $this->actingAs($this->doctorUser, 'sanctum')
            ->getJson('/api/v2/doctor/all-patients');
        $responseAllPatients->assertStatus(200);
        $this->assertTrue(collect($responseAllPatients->json('data'))->contains('appointment_id', $appointment->id));

        // 3. Doctor Patient Browser Detail Endpoint: GET /api/v2/doctor/patient-detail/{appointmentId}
        $responseDetail = $this->actingAs($this->doctorUser, 'sanctum')
            ->getJson('/api/v2/doctor/patient-detail/' . $appointment->id);
        $responseDetail->assertStatus(200);
        $responseDetail->assertJsonPath('data.id', $this->patient->id);
    }
}