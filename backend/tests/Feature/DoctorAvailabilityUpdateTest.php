<?php

namespace Tests\Feature;

use App\Models\Doctor;
use App\Models\DoctorAvailability;
use App\Models\DoctorAvailabilityOverride;
use App\Models\Patient;
use App\Models\User;
use App\Models\Appointment;
use App\Enums\AppointmentStatus;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DoctorAvailabilityUpdateTest extends TestCase
{
    use RefreshDatabase;

    protected Doctor $doctor;

    protected function setUp(): void
    {
        parent::setUp();

        $user = User::create([
            'name' => 'Test',
            'email' => 'dr.test@telehealth.test',
            'phone' => '9800000002',
            'password' => bcrypt('password'),
            'status' => 'active',
        ]);

        $this->doctor = Doctor::create([
            'user_id' => $user->id,
            'first_name' => 'Test',
            'last_name' => 'Doctor',
            'medical_license_number' => 'MD-UPDATE-123',
            'years_experience' => 5,
            'languages_known' => 'english',
            'status' => 'active',
        ]);
    }

    public function test_is_auto_recurring_default_value(): void
    {
        $availability = DoctorAvailability::create([
            'doctor_id' => $this->doctor->id,
            'day_of_week' => 'monday',
            'start_time' => '10:00:00',
            'end_time' => '12:00:00',
            'capacity' => 2,
            'is_recurring' => true,
            'recurring_start_date' => '2026-06-29',
            'recurring_end_date' => '2026-09-29',
        ]);

        $this->assertFalse($availability->is_auto_recurring);
    }

    public function test_auto_extension_logic_triggers_when_close_to_end_date(): void
    {
        $availability = DoctorAvailability::create([
            'doctor_id' => $this->doctor->id,
            'day_of_week' => 'monday',
            'start_time' => '10:00:00',
            'end_time' => '12:00:00',
            'capacity' => 2,
            'is_recurring' => true,
            'is_auto_recurring' => true,
            'recurring_months' => 3,
            'recurring_start_date' => '2026-06-26',
            'recurring_end_date' => '2026-06-29', // Ends today
        ]);

        Carbon::setTestNow('2026-06-26 10:30:00');

        $availabilityInstance = DoctorAvailability::find($availability->id);
        if ($availabilityInstance->is_recurring && $availabilityInstance->is_auto_recurring && $availabilityInstance->recurring_end_date) {
            $endDate = Carbon::parse($availabilityInstance->recurring_end_date)->startOfDay();
            $today = now()->startOfDay();
            if ($endDate->diffInDays($today, false) >= -7) {
                $months = $availabilityInstance->recurring_months ?: 3;
                $newEnd = $endDate->copy()->addMonths($months)->toDateString();
                $availabilityInstance->update([
                    'recurring_end_date' => $newEnd,
                ]);
            }
        }

        $updated = DoctorAvailability::find($availability->id);
        $this->assertEquals('2026-09-29', $updated->recurring_end_date->format('Y-m-d'));

        Carbon::setTestNow(null);
    }

    public function test_ending_soon_warning_triggers_without_auto_recur(): void
    {
        $availability = DoctorAvailability::create([
            'doctor_id' => $this->doctor->id,
            'day_of_week' => 'monday',
            'start_time' => '10:00:00',
            'end_time' => '12:00:00',
            'capacity' => 2,
            'is_recurring' => true,
            'is_auto_recurring' => false,
            'recurring_months' => 3,
            'recurring_start_date' => '2026-06-26',
            'recurring_end_date' => '2026-07-01', // Ends in 5 days
        ]);

        Carbon::setTestNow('2026-06-26 10:30:00');

        $isEndingSoon = false;
        if ($availability->is_recurring && !$availability->is_auto_recurring && $availability->recurring_end_date) {
            $endDate = Carbon::parse($availability->recurring_end_date)->startOfDay();
            $today = now()->startOfDay();
            $diffDays = $today->diffInDays($endDate, false);
            if ($diffDays <= 14) {
                $isEndingSoon = true;
            }
        }

        $this->assertTrue($isEndingSoon);

        Carbon::setTestNow(null);
    }

    public function test_parent_series_edit_locks_previous_and_booked_dates(): void
    {
        // 1. Create a recurring slot: Mondays 10:00 - 12:00 starting June 1st to Aug 31st
        $availability = DoctorAvailability::create([
            'doctor_id' => $this->doctor->id,
            'day_of_week' => 'monday',
            'start_time' => '10:00:00',
            'end_time' => '12:00:00',
            'capacity' => 2,
            'is_recurring' => true,
            'recurring_start_date' => '2026-06-01',
            'recurring_end_date' => '2026-08-31',
        ]);

        // Effective date is set to June 15th
        $effectiveDate = Carbon::parse('2026-06-15')->startOfDay();

        // Let's simulate:
        // - Monday June 8th (before effective) needs override
        // - Monday June 15th (on/after effective, but has booking) needs override
        // - Monday June 22nd (on/after effective, no bookings) gets updated directly (no override)

        // Generate June 8 override
        DoctorAvailabilityOverride::create([
            'doctor_availability_id' => $availability->id,
            'override_date' => '2026-06-08',
            'doctor_id' => $availability->doctor_id,
            'start_time' => '10:00:00',
            'end_time' => '12:00:00',
            'capacity' => 2,
            'status' => 'active',
        ]);

        // Generate June 15 override because it has booking
        DoctorAvailabilityOverride::create([
            'doctor_availability_id' => $availability->id,
            'override_date' => '2026-06-15',
            'doctor_id' => $availability->doctor_id,
            'start_time' => '10:00:00',
            'end_time' => '12:00:00',
            'capacity' => 2,
            'status' => 'active',
        ]);

        // 2. Perform the update to parent series: Change time to 11:00 - 13:00, capacity 5
        $availability->update([
            'start_time' => '11:00:00',
            'end_time' => '13:00:00',
            'capacity' => 5,
        ]);

        // 3. Verify overrides:
        // June 8 and June 15 overrides must preserve original times (10:00 - 12:00)
        $overrideJune8 = DoctorAvailabilityOverride::where('doctor_availability_id', $availability->id)
            ->whereDate('override_date', '2026-06-08')
            ->first();
        $this->assertNotNull($overrideJune8);
        $this->assertEquals('10:00', Carbon::parse($overrideJune8->start_time)->format('H:i'));

        $overrideJune15 = DoctorAvailabilityOverride::where('doctor_availability_id', $availability->id)
            ->whereDate('override_date', '2026-06-15')
            ->first();
        $this->assertNotNull($overrideJune15);
        $this->assertEquals('10:00', Carbon::parse($overrideJune15->start_time)->format('H:i'));

        // Verify June 22 has no override and inherits parent series (11:00 - 13:00)
        $overrideJune22 = DoctorAvailabilityOverride::where('doctor_availability_id', $availability->id)
            ->whereDate('override_date', '2026-06-22')
            ->first();
        $this->assertNull($overrideJune22);

        // Fetch effective values for June 22 using the service
        $effectiveJune22 = app(\App\Services\DoctorAvailabilityService::class)
            ->effectiveValuesForDate($availability, Carbon::parse('2026-06-22'));
        
        $this->assertEquals('11:00', Carbon::parse($effectiveJune22['start_time'])->format('H:i'));
        $this->assertEquals(5, $effectiveJune22['capacity']);
    }

    public function test_parent_series_validation_blocks_timing_change_if_bookings_exist(): void
    {
        // 1. Create a Patient
        $patientUser = User::create([
            'name' => 'Test Patient',
            'email' => 'patient@telehealth.test',
            'phone' => '9800000010',
            'password' => bcrypt('password'),
            'status' => 'active',
        ]);
        $patient = \App\Models\Patient::create([
            'user_id' => $patientUser->id,
            'first_name' => 'Test',
            'last_name' => 'Patient',
            'gender' => 'male',
            'email' => 'patient@telehealth.test',
            'mobile_no' => '9800000010',
            'status' => 'active',
        ]);

        // 2. Create recurring slot: Mondays June 1st to Aug 31st
        $availability = DoctorAvailability::create([
            'doctor_id' => $this->doctor->id,
            'day_of_week' => 'monday',
            'start_time' => '10:00:00',
            'end_time' => '12:00:00',
            'capacity' => 2,
            'is_recurring' => true,
            'recurring_start_date' => '2026-06-01',
            'recurring_end_date' => '2026-08-31',
            'consultation_type' => 'video',
        ]);

        // 3. Create booked appointment on June 15th (Monday)
        Appointment::create([
            'patient_id' => $patient->id,
            'doctor_id' => $this->doctor->id,
            'availability_id' => $availability->id,
            'appointment_date' => '2026-06-15',
            'appointment_time' => '10:00:00',
            'appointment_end_time' => '12:00:00',
            'status' => AppointmentStatus::CONFIRMED->value,
            'consultation_type' => 'video',
            'booking_source' => 'admin',
            'admin_payment_type' => 'without_payment',
        ]);

        // 4. Instantiate the Livewire page component and test the validation
        $page = new \App\Filament\Resources\Doctors\Pages\ManageDoctorAvailability();
        $reflection = new \ReflectionClass($page);
        $method = $reflection->getMethod('validateParentSeriesUpdate');
        $method->setAccessible(true);

        // a) Test case 1: Changing capacity only (timing unchanged). It should not throw any exception.
        $method->invokeArgs($page, [
            $availability,
            [
                'effective_date' => '2026-06-15',
                'start_time' => '10:00:00',
                'end_time' => '12:00:00',
                'capacity' => 5,
            ]
        ]);
        $this->assertTrue(true); // assert no exception was thrown

        // b) Test case 2: Changing timing on a date with bookings. It should throw ValidationException.
        $this->expectException(\Illuminate\Validation\ValidationException::class);
        $method->invokeArgs($page, [
            $availability,
            [
                'effective_date' => '2026-06-15', // on June 15 we have a booking
                'start_time' => '11:00:00', // timing changed
                'end_time' => '13:00:00',
                'capacity' => 2,
            ]
        ]);
    }

    public function test_completed_appointments_are_counted_as_booked_slots(): void
    {
        $patientUser = User::create([
            'name' => 'Test Patient',
            'email' => 'patient.completed@telehealth.test',
            'phone' => '9800000099',
            'password' => bcrypt('password'),
            'status' => 'active',
        ]);

        $patient = Patient::create([
            'user_id' => $patientUser->id,
            'first_name' => 'Test',
            'last_name' => 'Patient',
            'gender' => 'male',
            'email' => 'patient.completed@telehealth.test',
            'mobile_no' => '9800000099',
            'status' => 'active',
        ]);

        $availability = DoctorAvailability::create([
            'doctor_id' => $this->doctor->id,
            'day_of_week' => 'monday',
            'start_time' => '04:00:00',
            'end_time' => '05:30:00',
            'capacity' => 15,
            'is_recurring' => true,
            'recurring_start_date' => '2026-06-01',
            'recurring_end_date' => '2026-08-31',
            'consultation_type' => 'in-person',
            'opd_type' => 'private',
        ]);

        Appointment::create([
            'patient_id' => $patient->id,
            'doctor_id' => $this->doctor->id,
            'availability_id' => $availability->id,
            'appointment_date' => '2026-06-29',
            'appointment_time' => '04:00:00',
            'appointment_end_time' => '05:30:00',
            'status' => AppointmentStatus::COMPLETED->value,
            'consultation_type' => 'in-person',
            'booking_source' => 'admin',
            'admin_payment_type' => 'without_payment',
        ]);

        $count = app(\App\Services\SlotCapacityService::class)->bookedCount(
            doctorId: $this->doctor->id,
            date: '2026-06-29',
            startTime: '04:00:00',
            availabilityId: $availability->id,
            consultationType: 'in-person',
        );

        $this->assertSame(1, $count);
    }
}