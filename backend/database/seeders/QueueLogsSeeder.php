<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Doctor;
use App\Models\Patient;
use App\Models\Appointment;
use App\Models\AppointmentQueueLog;
use App\Models\DoctorAvailability;
use Carbon\Carbon;
use Illuminate\Support\Str;

class QueueLogsSeeder extends Seeder
{
    public function run(): void
    {
        $appTimezone = config('app.timezone') ?: 'UTC';
        $today = Carbon::today($appTimezone);
        $yesterday = Carbon::yesterday($appTimezone);
        $tomorrow = Carbon::tomorrow($appTimezone);

        // 1. Fetch Doctor Amit Sharma
        $doctor = Doctor::where('first_name', 'Amit')
            ->where('last_name', 'Sharma')
            ->first();

        if (!$doctor) {
            $doctor = Doctor::whereHas('user', function ($q) {
                $q->whereNotIn('email', ['mjoseph@gmail.com', 'kjoseph@gmail.com']);
            })->first();
        }

        if (!$doctor) {
            $this->command->warn('No doctors found to seed queue logs.');
            return;
        }

        // Clean existing availabilities, queue logs, and appointments specifically for this seeder
        DoctorAvailability::where('doctor_id', $doctor->id)->whereIn('date', [$yesterday->toDateString(), $today->toDateString(), $tomorrow->toDateString()])->forceDelete();
        AppointmentQueueLog::where('doctor_id', $doctor->id)->forceDelete();
        
        // Delete previous appointments we seeded with TOK-1xx and TOK-2xx to prevent duplicate key errors if re-run
        Appointment::where('doctor_id', $doctor->id)
            ->whereIn('appointment_date', [$yesterday->toDateString(), $today->toDateString(), $tomorrow->toDateString()])
            ->where(function($q) {
                $q->where('queue_number', 'like', 'TOK-1%')
                  ->orWhere('queue_number', 'like', 'TOK-2%');
            })
            ->forceDelete();

        // 2. Fetch Patients (from PatientSeeder)
        $patients = Patient::all();
        if ($patients->isEmpty()) {
            $this->command->warn('No patients found. Creating a fallback patient.');
            $fallbackPatient = Patient::create([
                'first_name' => 'Demo',
                'last_name' => 'Patient',
                'mobile_no' => '9800000000',
                'gender' => 'male',
                'date_of_birth' => '1990-05-15',
            ]);
            $patients = collect([$fallbackPatient]);
        }

        $systemUser = \App\Models\User::first();

        // Helper to get a random patient
        $getPatient = function($idx) use ($patients) {
            return $patients->get($idx % $patients->count());
        };

        // ==========================================
        // SCENARIO 1: YESTERDAY (PAST LOGS)
        // ==========================================
        // Scheduled Shifts yesterday: 09:00 - 11:00 and 13:00 - 15:00
        DoctorAvailability::create([
            'doctor_id' => $doctor->id,
            'date' => $yesterday->toDateString(),
            'start_time' => '09:00',
            'end_time' => '11:00',
            'capacity' => 10,
            'consultation_type' => 'in-person',
            'opd_type' => 'general',
            'is_available' => true,
        ]);
        DoctorAvailability::create([
            'doctor_id' => $doctor->id,
            'date' => $yesterday->toDateString(),
            'start_time' => '13:00',
            'end_time' => '15:00',
            'capacity' => 10,
            'consultation_type' => 'in-person',
            'opd_type' => 'general',
            'is_available' => true,
        ]);

        // Yesterday Shift 1 doctor check-in: 08:50:00 (On-time / early)
        AppointmentQueueLog::create([
            'doctor_id' => $doctor->id,
            'action' => 'check_in',
            'created_by' => $systemUser?->id,
            'created_at' => Carbon::parse($yesterday->toDateString() . ' 08:50:00', $appTimezone),
        ]);

        // Yesterday Appointment 1: Suresh, booked 09:00
        $app1Y = Appointment::create([
            'id' => Str::uuid(),
            'patient_id' => $getPatient(0)->id,
            'doctor_id' => $doctor->id,
            'appointment_date' => $yesterday->toDateString(),
            'appointment_time' => '09:00:00',
            'appointment_end_time' => '09:15:00',
            'consultation_type' => 'in-person',
            'status' => 'completed',
            'queue_status' => 'completed',
            'queue_number' => 'TOK-101',
            'slug' => 'amit-sharma-yesterday-tok-101',
        ]);
        AppointmentQueueLog::create([
            'doctor_id' => $doctor->id,
            'appointment_id' => $app1Y->id,
            'action' => 'revert',
            'queue_status' => 'checkin',
            'created_by' => $systemUser?->id,
            'created_at' => Carbon::parse($yesterday->toDateString() . ' 08:57:00', $appTimezone),
        ]);
        AppointmentQueueLog::create([
            'doctor_id' => $doctor->id,
            'appointment_id' => $app1Y->id,
            'action' => 'start',
            'queue_status' => 'started',
            'started_at' => Carbon::parse($yesterday->toDateString() . ' 09:02:00', $appTimezone),
            'created_by' => $systemUser?->id,
            'created_at' => Carbon::parse($yesterday->toDateString() . ' 09:02:00', $appTimezone),
        ]);
        AppointmentQueueLog::create([
            'doctor_id' => $doctor->id,
            'appointment_id' => $app1Y->id,
            'action' => 'complete',
            'queue_status' => 'completed',
            'started_at' => Carbon::parse($yesterday->toDateString() . ' 09:02:00', $appTimezone),
            'ended_at' => Carbon::parse($yesterday->toDateString() . ' 09:15:00', $appTimezone),
            'duration_seconds' => 780,
            'remarks' => 'Healthy report',
            'created_by' => $systemUser?->id,
            'created_at' => Carbon::parse($yesterday->toDateString() . ' 09:15:00', $appTimezone),
        ]);

        // Yesterday Appointment 2: Meera, booked 09:15
        $app2Y = Appointment::create([
            'id' => Str::uuid(),
            'patient_id' => $getPatient(1)->id,
            'doctor_id' => $doctor->id,
            'appointment_date' => $yesterday->toDateString(),
            'appointment_time' => '09:15:00',
            'appointment_end_time' => '09:30:00',
            'consultation_type' => 'in-person',
            'status' => 'completed',
            'queue_status' => 'completed',
            'queue_number' => 'TOK-102',
            'slug' => 'amit-sharma-yesterday-tok-102',
        ]);
        AppointmentQueueLog::create([
            'doctor_id' => $doctor->id,
            'appointment_id' => $app2Y->id,
            'action' => 'revert',
            'queue_status' => 'checkin',
            'created_by' => $systemUser?->id,
            'created_at' => Carbon::parse($yesterday->toDateString() . ' 09:10:00', $appTimezone),
        ]);
        AppointmentQueueLog::create([
            'doctor_id' => $doctor->id,
            'appointment_id' => $app2Y->id,
            'action' => 'start',
            'queue_status' => 'started',
            'started_at' => Carbon::parse($yesterday->toDateString() . ' 09:18:00', $appTimezone),
            'created_by' => $systemUser?->id,
            'created_at' => Carbon::parse($yesterday->toDateString() . ' 09:18:00', $appTimezone),
        ]);
        AppointmentQueueLog::create([
            'doctor_id' => $doctor->id,
            'appointment_id' => $app2Y->id,
            'action' => 'complete',
            'queue_status' => 'completed',
            'started_at' => Carbon::parse($yesterday->toDateString() . ' 09:18:00', $appTimezone),
            'ended_at' => Carbon::parse($yesterday->toDateString() . ' 09:35:00', $appTimezone),
            'duration_seconds' => 1020,
            'remarks' => 'Prescribed eye drops',
            'created_by' => $systemUser?->id,
            'created_at' => Carbon::parse($yesterday->toDateString() . ' 09:35:00', $appTimezone),
        ]);

        // Yesterday Break 1: 09:40:00 to 09:55:00 (15 mins)
        AppointmentQueueLog::create([
            'doctor_id' => $doctor->id,
            'action' => 'break_start',
            'created_by' => $systemUser?->id,
            'created_at' => Carbon::parse($yesterday->toDateString() . ' 09:40:00', $appTimezone),
        ]);
        AppointmentQueueLog::create([
            'doctor_id' => $doctor->id,
            'action' => 'break_end',
            'created_by' => $systemUser?->id,
            'created_at' => Carbon::parse($yesterday->toDateString() . ' 09:55:00', $appTimezone),
        ]);

        // Yesterday Appointment 3: Raj, booked 09:30
        $app3Y = Appointment::create([
            'id' => Str::uuid(),
            'patient_id' => $getPatient(2)->id,
            'doctor_id' => $doctor->id,
            'appointment_date' => $yesterday->toDateString(),
            'appointment_time' => '09:30:00',
            'appointment_end_time' => '09:45:00',
            'consultation_type' => 'in-person',
            'status' => 'completed',
            'queue_status' => 'completed',
            'queue_number' => 'TOK-103',
            'slug' => 'amit-sharma-yesterday-tok-103',
        ]);
        AppointmentQueueLog::create([
            'doctor_id' => $doctor->id,
            'appointment_id' => $app3Y->id,
            'action' => 'revert',
            'queue_status' => 'checkin',
            'created_by' => $systemUser?->id,
            'created_at' => Carbon::parse($yesterday->toDateString() . ' 09:25:00', $appTimezone),
        ]);
        AppointmentQueueLog::create([
            'doctor_id' => $doctor->id,
            'appointment_id' => $app3Y->id,
            'action' => 'start',
            'queue_status' => 'started',
            'started_at' => Carbon::parse($yesterday->toDateString() . ' 10:00:00', $appTimezone),
            'created_by' => $systemUser?->id,
            'created_at' => Carbon::parse($yesterday->toDateString() . ' 10:00:00', $appTimezone),
        ]);
        AppointmentQueueLog::create([
            'doctor_id' => $doctor->id,
            'appointment_id' => $app3Y->id,
            'action' => 'complete',
            'queue_status' => 'completed',
            'started_at' => Carbon::parse($yesterday->toDateString() . ' 10:00:00', $appTimezone),
            'ended_at' => Carbon::parse($yesterday->toDateString() . ' 10:20:00', $appTimezone),
            'duration_seconds' => 1200,
            'remarks' => 'Physiotherapy advised',
            'created_by' => $systemUser?->id,
            'created_at' => Carbon::parse($yesterday->toDateString() . ' 10:20:00', $appTimezone),
        ]);

        // Yesterday Appointment 4: Ravi, booked 10:00
        $app4Y = Appointment::create([
            'id' => Str::uuid(),
            'patient_id' => $getPatient(3)->id,
            'doctor_id' => $doctor->id,
            'appointment_date' => $yesterday->toDateString(),
            'appointment_time' => '10:00:00',
            'appointment_end_time' => '10:15:00',
            'consultation_type' => 'in-person',
            'status' => 'completed',
            'queue_status' => 'completed',
            'queue_number' => 'TOK-104',
            'slug' => 'amit-sharma-yesterday-tok-104',
        ]);
        AppointmentQueueLog::create([
            'doctor_id' => $doctor->id,
            'appointment_id' => $app4Y->id,
            'action' => 'revert',
            'queue_status' => 'checkin',
            'created_by' => $systemUser?->id,
            'created_at' => Carbon::parse($yesterday->toDateString() . ' 09:50:00', $appTimezone),
        ]);
        AppointmentQueueLog::create([
            'doctor_id' => $doctor->id,
            'appointment_id' => $app4Y->id,
            'action' => 'start',
            'queue_status' => 'started',
            'started_at' => Carbon::parse($yesterday->toDateString() . ' 10:22:00', $appTimezone),
            'created_by' => $systemUser?->id,
            'created_at' => Carbon::parse($yesterday->toDateString() . ' 10:22:00', $appTimezone),
        ]);
        AppointmentQueueLog::create([
            'doctor_id' => $doctor->id,
            'appointment_id' => $app4Y->id,
            'action' => 'complete',
            'queue_status' => 'completed',
            'started_at' => Carbon::parse($yesterday->toDateString() . ' 10:22:00', $appTimezone),
            'ended_at' => Carbon::parse($yesterday->toDateString() . ' 10:45:00', $appTimezone),
            'duration_seconds' => 1380,
            'remarks' => 'Completed yesterday Shift 1',
            'created_by' => $systemUser?->id,
            'created_at' => Carbon::parse($yesterday->toDateString() . ' 10:45:00', $appTimezone),
        ]);

        // Yesterday Shift 2 doctor check-in: 12:55:00
        AppointmentQueueLog::create([
            'doctor_id' => $doctor->id,
            'action' => 'check_in',
            'created_by' => $systemUser?->id,
            'created_at' => Carbon::parse($yesterday->toDateString() . ' 12:55:00', $appTimezone),
        ]);

        // Yesterday Appointment 5: Patient Priya, booked 13:00
        $app5Y = Appointment::create([
            'id' => Str::uuid(),
            'patient_id' => $getPatient(4)->id,
            'doctor_id' => $doctor->id,
            'appointment_date' => $yesterday->toDateString(),
            'appointment_time' => '13:00:00',
            'appointment_end_time' => '13:15:00',
            'consultation_type' => 'in-person',
            'status' => 'completed',
            'queue_status' => 'completed',
            'queue_number' => 'TOK-105',
            'slug' => 'amit-sharma-yesterday-tok-105',
        ]);
        AppointmentQueueLog::create([
            'doctor_id' => $doctor->id,
            'appointment_id' => $app5Y->id,
            'action' => 'revert',
            'queue_status' => 'checkin',
            'created_by' => $systemUser?->id,
            'created_at' => Carbon::parse($yesterday->toDateString() . ' 12:58:00', $appTimezone),
        ]);
        AppointmentQueueLog::create([
            'doctor_id' => $doctor->id,
            'appointment_id' => $app5Y->id,
            'action' => 'start',
            'queue_status' => 'started',
            'started_at' => Carbon::parse($yesterday->toDateString() . ' 13:02:00', $appTimezone),
            'created_by' => $systemUser?->id,
            'created_at' => Carbon::parse($yesterday->toDateString() . ' 13:02:00', $appTimezone),
        ]);
        AppointmentQueueLog::create([
            'doctor_id' => $doctor->id,
            'appointment_id' => $app5Y->id,
            'action' => 'complete',
            'queue_status' => 'completed',
            'started_at' => Carbon::parse($yesterday->toDateString() . ' 13:02:00', $appTimezone),
            'ended_at' => Carbon::parse($yesterday->toDateString() . ' 13:20:00', $appTimezone),
            'duration_seconds' => 1080,
            'remarks' => 'Completed consult',
            'created_by' => $systemUser?->id,
            'created_at' => Carbon::parse($yesterday->toDateString() . ' 13:20:00', $appTimezone),
        ]);

        // Yesterday Appointment 6: Patient Vikram, booked 13:15
        $app6Y = Appointment::create([
            'id' => Str::uuid(),
            'patient_id' => $getPatient(5)->id,
            'doctor_id' => $doctor->id,
            'appointment_date' => $yesterday->toDateString(),
            'appointment_time' => '13:15:00',
            'appointment_end_time' => '13:30:00',
            'consultation_type' => 'in-person',
            'status' => 'completed',
            'queue_status' => 'completed',
            'queue_number' => 'TOK-106',
            'slug' => 'amit-sharma-yesterday-tok-106',
        ]);
        AppointmentQueueLog::create([
            'doctor_id' => $doctor->id,
            'appointment_id' => $app6Y->id,
            'action' => 'revert',
            'queue_status' => 'checkin',
            'created_by' => $systemUser?->id,
            'created_at' => Carbon::parse($yesterday->toDateString() . ' 13:10:00', $appTimezone),
        ]);
        AppointmentQueueLog::create([
            'doctor_id' => $doctor->id,
            'appointment_id' => $app6Y->id,
            'action' => 'start',
            'queue_status' => 'started',
            'started_at' => Carbon::parse($yesterday->toDateString() . ' 13:22:00', $appTimezone),
            'created_by' => $systemUser?->id,
            'created_at' => Carbon::parse($yesterday->toDateString() . ' 13:22:00', $appTimezone),
        ]);
        AppointmentQueueLog::create([
            'doctor_id' => $doctor->id,
            'appointment_id' => $app6Y->id,
            'action' => 'complete',
            'queue_status' => 'completed',
            'started_at' => Carbon::parse($yesterday->toDateString() . ' 13:22:00', $appTimezone),
            'ended_at' => Carbon::parse($yesterday->toDateString() . ' 13:45:00', $appTimezone),
            'duration_seconds' => 1380,
            'remarks' => 'Completed consult 6',
            'created_by' => $systemUser?->id,
            'created_at' => Carbon::parse($yesterday->toDateString() . ' 13:45:00', $appTimezone),
        ]);

        // Yesterday Break 2: 13:50:00 to 14:05:00 (15 mins)
        AppointmentQueueLog::create([
            'doctor_id' => $doctor->id,
            'action' => 'break_start',
            'created_by' => $systemUser?->id,
            'created_at' => Carbon::parse($yesterday->toDateString() . ' 13:50:00', $appTimezone),
        ]);
        AppointmentQueueLog::create([
            'doctor_id' => $doctor->id,
            'action' => 'break_end',
            'created_by' => $systemUser?->id,
            'created_at' => Carbon::parse($yesterday->toDateString() . ' 14:05:00', $appTimezone),
        ]);

        // Yesterday Appointment 7: Patient Sunita, booked 13:30
        $app7Y = Appointment::create([
            'id' => Str::uuid(),
            'patient_id' => $getPatient(6)->id,
            'doctor_id' => $doctor->id,
            'appointment_date' => $yesterday->toDateString(),
            'appointment_time' => '13:30:00',
            'appointment_end_time' => '13:45:00',
            'consultation_type' => 'in-person',
            'status' => 'completed',
            'queue_status' => 'completed',
            'queue_number' => 'TOK-107',
            'slug' => 'amit-sharma-yesterday-tok-107',
        ]);
        AppointmentQueueLog::create([
            'doctor_id' => $doctor->id,
            'appointment_id' => $app7Y->id,
            'action' => 'revert',
            'queue_status' => 'checkin',
            'created_by' => $systemUser?->id,
            'created_at' => Carbon::parse($yesterday->toDateString() . ' 13:25:00', $appTimezone),
        ]);
        AppointmentQueueLog::create([
            'doctor_id' => $doctor->id,
            'appointment_id' => $app7Y->id,
            'action' => 'start',
            'queue_status' => 'started',
            'started_at' => Carbon::parse($yesterday->toDateString() . ' 14:10:00', $appTimezone),
            'created_by' => $systemUser?->id,
            'created_at' => Carbon::parse($yesterday->toDateString() . ' 14:10:00', $appTimezone),
        ]);
        AppointmentQueueLog::create([
            'doctor_id' => $doctor->id,
            'appointment_id' => $app7Y->id,
            'action' => 'complete',
            'queue_status' => 'completed',
            'started_at' => Carbon::parse($yesterday->toDateString() . ' 14:10:00', $appTimezone),
            'ended_at' => Carbon::parse($yesterday->toDateString() . ' 14:35:00', $appTimezone),
            'duration_seconds' => 1500,
            'remarks' => 'Completed consult 7',
            'created_by' => $systemUser?->id,
            'created_at' => Carbon::parse($yesterday->toDateString() . ' 14:35:00', $appTimezone),
        ]);

        // Yesterday Appointment 8: Patient Rahul, booked 14:00
        $app8Y = Appointment::create([
            'id' => Str::uuid(),
            'patient_id' => $getPatient(7)->id,
            'doctor_id' => $doctor->id,
            'appointment_date' => $yesterday->toDateString(),
            'appointment_time' => '14:00:00',
            'appointment_end_time' => '14:15:00',
            'consultation_type' => 'in-person',
            'status' => 'completed',
            'queue_status' => 'completed',
            'queue_number' => 'TOK-108',
            'slug' => 'amit-sharma-yesterday-tok-108',
        ]);
        AppointmentQueueLog::create([
            'doctor_id' => $doctor->id,
            'appointment_id' => $app8Y->id,
            'action' => 'revert',
            'queue_status' => 'checkin',
            'created_by' => $systemUser?->id,
            'created_at' => Carbon::parse($yesterday->toDateString() . ' 13:55:00', $appTimezone),
        ]);
        AppointmentQueueLog::create([
            'doctor_id' => $doctor->id,
            'appointment_id' => $app8Y->id,
            'action' => 'start',
            'queue_status' => 'started',
            'started_at' => Carbon::parse($yesterday->toDateString() . ' 14:38:00', $appTimezone),
            'created_by' => $systemUser?->id,
            'created_at' => Carbon::parse($yesterday->toDateString() . ' 14:38:00', $appTimezone),
        ]);
        AppointmentQueueLog::create([
            'doctor_id' => $doctor->id,
            'appointment_id' => $app8Y->id,
            'action' => 'complete',
            'queue_status' => 'completed',
            'started_at' => Carbon::parse($yesterday->toDateString() . ' 14:38:00', $appTimezone),
            'ended_at' => Carbon::parse($yesterday->toDateString() . ' 15:15:00', $appTimezone),
            'duration_seconds' => 2220,
            'remarks' => 'Completed yesterday Shift 2 (15 mins extra time worked!)',
            'created_by' => $systemUser?->id,
            'created_at' => Carbon::parse($yesterday->toDateString() . ' 15:15:00', $appTimezone),
        ]);

        // Yesterday Shift 3: 17:00 - 19:00
        DoctorAvailability::create([
            'doctor_id' => $doctor->id,
            'date' => $yesterday->toDateString(),
            'start_time' => '17:00',
            'end_time' => '19:00',
            'capacity' => 10,
            'consultation_type' => 'in-person',
            'opd_type' => 'general',
            'is_available' => true,
        ]);

        // Yesterday Shift 3 doctor check-in: 16:55:00 (5 mins early)
        AppointmentQueueLog::create([
            'doctor_id' => $doctor->id,
            'action' => 'check_in',
            'created_by' => $systemUser?->id,
            'created_at' => Carbon::parse($yesterday->toDateString() . ' 16:55:00', $appTimezone),
        ]);

        // Yesterday Appointment 9: Amit, booked 17:00
        $app9Y = Appointment::create([
            'id' => Str::uuid(),
            'patient_id' => $getPatient(8)->id,
            'doctor_id' => $doctor->id,
            'appointment_date' => $yesterday->toDateString(),
            'appointment_time' => '17:00:00',
            'appointment_end_time' => '17:15:00',
            'consultation_type' => 'in-person',
            'status' => 'completed',
            'queue_status' => 'completed',
            'queue_number' => 'TOK-109',
            'slug' => 'amit-sharma-yesterday-tok-109',
        ]);
        AppointmentQueueLog::create([
            'doctor_id' => $doctor->id,
            'appointment_id' => $app9Y->id,
            'action' => 'revert',
            'queue_status' => 'checkin',
            'created_by' => $systemUser?->id,
            'created_at' => Carbon::parse($yesterday->toDateString() . ' 16:58:00', $appTimezone),
        ]);
        AppointmentQueueLog::create([
            'doctor_id' => $doctor->id,
            'appointment_id' => $app9Y->id,
            'action' => 'start',
            'queue_status' => 'started',
            'started_at' => Carbon::parse($yesterday->toDateString() . ' 17:05:00', $appTimezone),
            'created_by' => $systemUser?->id,
            'created_at' => Carbon::parse($yesterday->toDateString() . ' 17:05:00', $appTimezone),
        ]);
        AppointmentQueueLog::create([
            'doctor_id' => $doctor->id,
            'appointment_id' => $app9Y->id,
            'action' => 'complete',
            'queue_status' => 'completed',
            'started_at' => Carbon::parse($yesterday->toDateString() . ' 17:05:00', $appTimezone),
            'ended_at' => Carbon::parse($yesterday->toDateString() . ' 17:35:00', $appTimezone),
            'duration_seconds' => 1800,
            'remarks' => 'Completed evening shift patient 1',
            'created_by' => $systemUser?->id,
            'created_at' => Carbon::parse($yesterday->toDateString() . ' 17:35:00', $appTimezone),
        ]);

        // Yesterday Appointment 10: Seema, booked 17:30
        $app10Y = Appointment::create([
            'id' => Str::uuid(),
            'patient_id' => $getPatient(9)->id,
            'doctor_id' => $doctor->id,
            'appointment_date' => $yesterday->toDateString(),
            'appointment_time' => '17:30:00',
            'appointment_end_time' => '17:45:00',
            'consultation_type' => 'in-person',
            'status' => 'completed',
            'queue_status' => 'completed',
            'queue_number' => 'TOK-110',
            'slug' => 'amit-sharma-yesterday-tok-110',
        ]);
        AppointmentQueueLog::create([
            'doctor_id' => $doctor->id,
            'appointment_id' => $app10Y->id,
            'action' => 'revert',
            'queue_status' => 'checkin',
            'created_by' => $systemUser?->id,
            'created_at' => Carbon::parse($yesterday->toDateString() . ' 17:28:00', $appTimezone),
        ]);
        AppointmentQueueLog::create([
            'doctor_id' => $doctor->id,
            'appointment_id' => $app10Y->id,
            'action' => 'start',
            'queue_status' => 'started',
            'started_at' => Carbon::parse($yesterday->toDateString() . ' 17:40:00', $appTimezone),
            'created_by' => $systemUser?->id,
            'created_at' => Carbon::parse($yesterday->toDateString() . ' 17:40:00', $appTimezone),
        ]);
        AppointmentQueueLog::create([
            'doctor_id' => $doctor->id,
            'appointment_id' => $app10Y->id,
            'action' => 'complete',
            'queue_status' => 'completed',
            'started_at' => Carbon::parse($yesterday->toDateString() . ' 17:40:00', $appTimezone),
            'ended_at' => Carbon::parse($yesterday->toDateString() . ' 18:10:00', $appTimezone),
            'duration_seconds' => 1800,
            'remarks' => 'Completed evening shift patient 2',
            'created_by' => $systemUser?->id,
            'created_at' => Carbon::parse($yesterday->toDateString() . ' 18:10:00', $appTimezone),
        ]);

        // Yesterday Break 3: 18:15:00 to 18:30:00 (15 mins)
        AppointmentQueueLog::create([
            'doctor_id' => $doctor->id,
            'action' => 'break_start',
            'created_by' => $systemUser?->id,
            'created_at' => Carbon::parse($yesterday->toDateString() . ' 18:15:00', $appTimezone),
        ]);
        AppointmentQueueLog::create([
            'doctor_id' => $doctor->id,
            'action' => 'break_end',
            'created_by' => $systemUser?->id,
            'created_at' => Carbon::parse($yesterday->toDateString() . ' 18:30:00', $appTimezone),
        ]);

        // Yesterday Appointment 11: Vikas, booked 18:30
        $app11Y = Appointment::create([
            'id' => Str::uuid(),
            'patient_id' => $getPatient(10)->id,
            'doctor_id' => $doctor->id,
            'appointment_date' => $yesterday->toDateString(),
            'appointment_time' => '18:30:00',
            'appointment_end_time' => '18:45:00',
            'consultation_type' => 'in-person',
            'status' => 'completed',
            'queue_status' => 'completed',
            'queue_number' => 'TOK-111',
            'slug' => 'amit-sharma-yesterday-tok-111',
        ]);
        AppointmentQueueLog::create([
            'doctor_id' => $doctor->id,
            'appointment_id' => $app11Y->id,
            'action' => 'revert',
            'queue_status' => 'checkin',
            'created_by' => $systemUser?->id,
            'created_at' => Carbon::parse($yesterday->toDateString() . ' 18:25:00', $appTimezone),
        ]);
        AppointmentQueueLog::create([
            'doctor_id' => $doctor->id,
            'appointment_id' => $app11Y->id,
            'action' => 'start',
            'queue_status' => 'started',
            'started_at' => Carbon::parse($yesterday->toDateString() . ' 18:35:00', $appTimezone),
            'created_by' => $systemUser?->id,
            'created_at' => Carbon::parse($yesterday->toDateString() . ' 18:35:00', $appTimezone),
        ]);
        AppointmentQueueLog::create([
            'doctor_id' => $doctor->id,
            'appointment_id' => $app11Y->id,
            'action' => 'complete',
            'queue_status' => 'completed',
            'started_at' => Carbon::parse($yesterday->toDateString() . ' 18:35:00', $appTimezone),
            'ended_at' => Carbon::parse($yesterday->toDateString() . ' 19:15:00', $appTimezone),
            'duration_seconds' => 2400,
            'remarks' => 'Completed evening shift (15 mins extra time worked!)',
            'created_by' => $systemUser?->id,
            'created_at' => Carbon::parse($yesterday->toDateString() . ' 19:15:00', $appTimezone),
        ]);


        // ==========================================
        // SCENARIO 2: TODAY (CURRENT LOGS)
        // ==========================================
        // Scheduled Shifts today: 09:00 - 11:00 and 13:00 - 15:00
        DoctorAvailability::create([
            'doctor_id' => $doctor->id,
            'date' => $today->toDateString(),
            'start_time' => '09:00',
            'end_time' => '11:00',
            'capacity' => 10,
            'consultation_type' => 'in-person',
            'opd_type' => 'general',
            'is_available' => true,
        ]);
        DoctorAvailability::create([
            'doctor_id' => $doctor->id,
            'date' => $today->toDateString(),
            'start_time' => '13:00',
            'end_time' => '15:00',
            'capacity' => 10,
            'consultation_type' => 'in-person',
            'opd_type' => 'general',
            'is_available' => true,
        ]);

        // Today Shift 1 doctor check-in: 09:05:00 (5 mins late for 09:00 slot start)
        AppointmentQueueLog::create([
            'doctor_id' => $doctor->id,
            'action' => 'check_in',
            'created_by' => $systemUser?->id,
            'created_at' => Carbon::parse($today->toDateString() . ' 09:05:00', $appTimezone),
        ]);

        // Today Appointment 1: Suresh, booked 09:00
        $app1 = Appointment::create([
            'id' => Str::uuid(),
            'patient_id' => $getPatient(0)->id,
            'doctor_id' => $doctor->id,
            'appointment_date' => $today->toDateString(),
            'appointment_time' => '09:00:00',
            'appointment_end_time' => '09:15:00',
            'consultation_type' => 'in-person',
            'status' => 'completed',
            'queue_status' => 'completed',
            'queue_number' => 'TOK-201',
            'slug' => 'amit-sharma-today-tok-201',
        ]);
        AppointmentQueueLog::create([
            'doctor_id' => $doctor->id,
            'appointment_id' => $app1->id,
            'action' => 'revert',
            'queue_status' => 'checkin',
            'created_by' => $systemUser?->id,
            'created_at' => Carbon::parse($today->toDateString() . ' 09:08:00', $appTimezone),
        ]);
        AppointmentQueueLog::create([
            'doctor_id' => $doctor->id,
            'appointment_id' => $app1->id,
            'action' => 'start',
            'queue_status' => 'started',
            'started_at' => Carbon::parse($today->toDateString() . ' 09:12:00', $appTimezone),
            'created_by' => $systemUser?->id,
            'created_at' => Carbon::parse($today->toDateString() . ' 09:12:00', $appTimezone),
        ]);
        AppointmentQueueLog::create([
            'doctor_id' => $doctor->id,
            'appointment_id' => $app1->id,
            'action' => 'complete',
            'queue_status' => 'completed',
            'started_at' => Carbon::parse($today->toDateString() . ' 09:12:00', $appTimezone),
            'ended_at' => Carbon::parse($today->toDateString() . ' 09:28:00', $appTimezone),
            'duration_seconds' => 960,
            'remarks' => 'Prescribed basic multi-vitamins',
            'created_by' => $systemUser?->id,
            'created_at' => Carbon::parse($today->toDateString() . ' 09:28:00', $appTimezone),
        ]);

        // Today Appointment 2: Meera, booked 09:15
        $app2 = Appointment::create([
            'id' => Str::uuid(),
            'patient_id' => $getPatient(1)->id,
            'doctor_id' => $doctor->id,
            'appointment_date' => $today->toDateString(),
            'appointment_time' => '09:15:00',
            'appointment_end_time' => '09:30:00',
            'consultation_type' => 'in-person',
            'status' => 'completed',
            'queue_status' => 'completed',
            'queue_number' => 'TOK-202',
            'slug' => 'amit-sharma-today-tok-202',
        ]);
        AppointmentQueueLog::create([
            'doctor_id' => $doctor->id,
            'appointment_id' => $app2->id,
            'action' => 'revert',
            'queue_status' => 'checkin',
            'created_by' => $systemUser?->id,
            'created_at' => Carbon::parse($today->toDateString() . ' 09:14:00', $appTimezone),
        ]);
        AppointmentQueueLog::create([
            'doctor_id' => $doctor->id,
            'appointment_id' => $app2->id,
            'action' => 'start',
            'queue_status' => 'started',
            'started_at' => Carbon::parse($today->toDateString() . ' 09:30:00', $appTimezone),
            'created_by' => $systemUser?->id,
            'created_at' => Carbon::parse($today->toDateString() . ' 09:30:00', $appTimezone),
        ]);
        AppointmentQueueLog::create([
            'doctor_id' => $doctor->id,
            'appointment_id' => $app2->id,
            'action' => 'complete',
            'queue_status' => 'completed',
            'started_at' => Carbon::parse($today->toDateString() . ' 09:30:00', $appTimezone),
            'ended_at' => Carbon::parse($today->toDateString() . ' 09:48:00', $appTimezone),
            'duration_seconds' => 1080,
            'remarks' => 'Review in next session',
            'created_by' => $systemUser?->id,
            'created_at' => Carbon::parse($today->toDateString() . ' 09:48:00', $appTimezone),
        ]);

        // Today Break 1: 09:50:00 to 10:05:00 (15 mins)
        AppointmentQueueLog::create([
            'doctor_id' => $doctor->id,
            'action' => 'break_start',
            'created_by' => $systemUser?->id,
            'created_at' => Carbon::parse($today->toDateString() . ' 09:50:00', $appTimezone),
        ]);
        AppointmentQueueLog::create([
            'doctor_id' => $doctor->id,
            'action' => 'break_end',
            'created_by' => $systemUser?->id,
            'created_at' => Carbon::parse($today->toDateString() . ' 10:05:00', $appTimezone),
        ]);

        // Today Appointment 3: Raj, booked 09:30
        $app3 = Appointment::create([
            'id' => Str::uuid(),
            'patient_id' => $getPatient(2)->id,
            'doctor_id' => $doctor->id,
            'appointment_date' => $today->toDateString(),
            'appointment_time' => '09:30:00',
            'appointment_end_time' => '09:45:00',
            'consultation_type' => 'in-person',
            'status' => 'completed',
            'queue_status' => 'completed',
            'queue_number' => 'TOK-203',
            'slug' => 'amit-sharma-today-tok-203',
        ]);
        AppointmentQueueLog::create([
            'doctor_id' => $doctor->id,
            'appointment_id' => $app3->id,
            'action' => 'revert',
            'queue_status' => 'checkin',
            'created_by' => $systemUser?->id,
            'created_at' => Carbon::parse($today->toDateString() . ' 09:28:00', $appTimezone),
        ]);
        AppointmentQueueLog::create([
            'doctor_id' => $doctor->id,
            'appointment_id' => $app3->id,
            'action' => 'start',
            'queue_status' => 'started',
            'started_at' => Carbon::parse($today->toDateString() . ' 10:08:00', $appTimezone),
            'created_by' => $systemUser?->id,
            'created_at' => Carbon::parse($today->toDateString() . ' 10:08:00', $appTimezone),
        ]);
        AppointmentQueueLog::create([
            'doctor_id' => $doctor->id,
            'appointment_id' => $app3->id,
            'action' => 'complete',
            'queue_status' => 'completed',
            'started_at' => Carbon::parse($today->toDateString() . ' 10:08:00', $appTimezone),
            'ended_at' => Carbon::parse($today->toDateString() . ' 10:30:00', $appTimezone),
            'duration_seconds' => 1320,
            'remarks' => 'Treated today',
            'created_by' => $systemUser?->id,
            'created_at' => Carbon::parse($today->toDateString() . ' 10:30:00', $appTimezone),
        ]);

        // Today Appointment 4: Ravi, booked 10:00
        $app4 = Appointment::create([
            'id' => Str::uuid(),
            'patient_id' => $getPatient(3)->id,
            'doctor_id' => $doctor->id,
            'appointment_date' => $today->toDateString(),
            'appointment_time' => '10:00:00',
            'appointment_end_time' => '10:15:00',
            'consultation_type' => 'in-person',
            'status' => 'completed',
            'queue_status' => 'completed',
            'queue_number' => 'TOK-204',
            'slug' => 'amit-sharma-today-tok-204',
        ]);
        AppointmentQueueLog::create([
            'doctor_id' => $doctor->id,
            'appointment_id' => $app4->id,
            'action' => 'revert',
            'queue_status' => 'checkin',
            'created_by' => $systemUser?->id,
            'created_at' => Carbon::parse($today->toDateString() . ' 09:58:00', $appTimezone),
        ]);
        AppointmentQueueLog::create([
            'doctor_id' => $doctor->id,
            'appointment_id' => $app4->id,
            'action' => 'start',
            'queue_status' => 'started',
            'started_at' => Carbon::parse($today->toDateString() . ' 10:32:00', $appTimezone),
            'created_by' => $systemUser?->id,
            'created_at' => Carbon::parse($today->toDateString() . ' 10:32:00', $appTimezone),
        ]);
        AppointmentQueueLog::create([
            'doctor_id' => $doctor->id,
            'appointment_id' => $app4->id,
            'action' => 'complete',
            'queue_status' => 'completed',
            'started_at' => Carbon::parse($today->toDateString() . ' 10:32:00', $appTimezone),
            'ended_at' => Carbon::parse($today->toDateString() . ' 10:55:00', $appTimezone),
            'duration_seconds' => 1380,
            'remarks' => 'Completed Shift 1',
            'created_by' => $systemUser?->id,
            'created_at' => Carbon::parse($today->toDateString() . ' 10:55:00', $appTimezone),
        ]);

        // Today Shift 2 doctor check-in: 11:51:11 (This check-in is early for Shift 2 starting at 13:00, or late for Shift 1. Closest is Shift 2!)
        AppointmentQueueLog::create([
            'doctor_id' => $doctor->id,
            'action' => 'check_in',
            'created_by' => $systemUser?->id,
            'created_at' => Carbon::parse($today->toDateString() . ' 11:51:11', $appTimezone),
        ]);

        // Today Appointment 5: Patient Priya, booked 13:00
        $app5 = Appointment::create([
            'id' => Str::uuid(),
            'patient_id' => $getPatient(4)->id,
            'doctor_id' => $doctor->id,
            'appointment_date' => $today->toDateString(),
            'appointment_time' => '13:00:00',
            'appointment_end_time' => '13:15:00',
            'consultation_type' => 'in-person',
            'status' => 'completed',
            'queue_status' => 'completed',
            'queue_number' => 'TOK-205',
            'slug' => 'amit-sharma-today-tok-205',
        ]);
        AppointmentQueueLog::create([
            'doctor_id' => $doctor->id,
            'appointment_id' => $app5->id,
            'action' => 'revert',
            'queue_status' => 'checkin',
            'created_by' => $systemUser?->id,
            'created_at' => Carbon::parse($today->toDateString() . ' 13:04:00', $appTimezone),
        ]);
        AppointmentQueueLog::create([
            'doctor_id' => $doctor->id,
            'appointment_id' => $app5->id,
            'action' => 'start',
            'queue_status' => 'started',
            'started_at' => Carbon::parse($today->toDateString() . ' 13:08:00', $appTimezone),
            'created_by' => $systemUser?->id,
            'created_at' => Carbon::parse($today->toDateString() . ' 13:08:00', $appTimezone),
        ]);
        AppointmentQueueLog::create([
            'doctor_id' => $doctor->id,
            'appointment_id' => $app5->id,
            'action' => 'complete',
            'queue_status' => 'completed',
            'started_at' => Carbon::parse($today->toDateString() . ' 13:08:00', $appTimezone),
            'ended_at' => Carbon::parse($today->toDateString() . ' 13:28:00', $appTimezone),
            'duration_seconds' => 1200,
            'remarks' => 'Completed today 5',
            'created_by' => $systemUser?->id,
            'created_at' => Carbon::parse($today->toDateString() . ' 13:28:00', $appTimezone),
        ]);

        // Today Appointment 6: Patient Vikram, booked 13:15
        $app6 = Appointment::create([
            'id' => Str::uuid(),
            'patient_id' => $getPatient(5)->id,
            'doctor_id' => $doctor->id,
            'appointment_date' => $today->toDateString(),
            'appointment_time' => '13:15:00',
            'appointment_end_time' => '13:30:00',
            'consultation_type' => 'in-person',
            'status' => 'completed',
            'queue_status' => 'completed',
            'queue_number' => 'TOK-206',
            'slug' => 'amit-sharma-today-tok-206',
        ]);
        AppointmentQueueLog::create([
            'doctor_id' => $doctor->id,
            'appointment_id' => $app6->id,
            'action' => 'revert',
            'queue_status' => 'checkin',
            'created_by' => $systemUser?->id,
            'created_at' => Carbon::parse($today->toDateString() . ' 13:12:00', $appTimezone),
        ]);
        AppointmentQueueLog::create([
            'doctor_id' => $doctor->id,
            'appointment_id' => $app6->id,
            'action' => 'start',
            'queue_status' => 'started',
            'started_at' => Carbon::parse($today->toDateString() . ' 13:30:00', $appTimezone),
            'created_by' => $systemUser?->id,
            'created_at' => Carbon::parse($today->toDateString() . ' 13:30:00', $appTimezone),
        ]);
        AppointmentQueueLog::create([
            'doctor_id' => $doctor->id,
            'appointment_id' => $app6->id,
            'action' => 'complete',
            'queue_status' => 'completed',
            'started_at' => Carbon::parse($today->toDateString() . ' 13:30:00', $appTimezone),
            'ended_at' => Carbon::parse($today->toDateString() . ' 13:52:00', $appTimezone),
            'duration_seconds' => 1320,
            'remarks' => 'Completed today 6',
            'created_by' => $systemUser?->id,
            'created_at' => Carbon::parse($today->toDateString() . ' 13:52:00', $appTimezone),
        ]);

        // Break 2 Start: 13:55:00, End: 14:10:00 (15 mins break today)
        AppointmentQueueLog::create([
            'doctor_id' => $doctor->id,
            'action' => 'break_start',
            'created_by' => $systemUser?->id,
            'created_at' => Carbon::parse($today->toDateString() . ' 13:55:00', $appTimezone),
        ]);
        AppointmentQueueLog::create([
            'doctor_id' => $doctor->id,
            'action' => 'break_end',
            'created_by' => $systemUser?->id,
            'created_at' => Carbon::parse($today->toDateString() . ' 14:10:00', $appTimezone),
        ]);

        // Today Appointment 7: Patient Sunita, booked 13:30 (Skipped)
        $app7 = Appointment::create([
            'id' => Str::uuid(),
            'patient_id' => $getPatient(6)->id,
            'doctor_id' => $doctor->id,
            'appointment_date' => $today->toDateString(),
            'appointment_time' => '13:30:00',
            'appointment_end_time' => '13:45:00',
            'consultation_type' => 'in-person',
            'status' => 'confirmed',
            'queue_status' => 'skipped',
            'queue_number' => 'TOK-207',
            'slug' => 'amit-sharma-today-tok-207',
        ]);
        AppointmentQueueLog::create([
            'doctor_id' => $doctor->id,
            'appointment_id' => $app7->id,
            'action' => 'revert',
            'queue_status' => 'checkin',
            'created_by' => $systemUser?->id,
            'created_at' => Carbon::parse($today->toDateString() . ' 13:28:00', $appTimezone),
        ]);
        AppointmentQueueLog::create([
            'doctor_id' => $doctor->id,
            'appointment_id' => $app7->id,
            'action' => 'skip',
            'queue_status' => 'skipped',
            'remarks' => 'Patient not in waiting area',
            'created_by' => $systemUser?->id,
            'created_at' => Carbon::parse($today->toDateString() . ' 14:12:00', $appTimezone),
        ]);

        // Today Appointment 8: Patient Rahul, booked 14:00
        $app8 = Appointment::create([
            'id' => Str::uuid(),
            'patient_id' => $getPatient(7)->id,
            'doctor_id' => $doctor->id,
            'appointment_date' => $today->toDateString(),
            'appointment_time' => '14:00:00',
            'appointment_end_time' => '14:15:00',
            'consultation_type' => 'in-person',
            'status' => 'completed',
            'queue_status' => 'completed',
            'queue_number' => 'TOK-208',
            'slug' => 'amit-sharma-today-tok-208',
        ]);
        AppointmentQueueLog::create([
            'doctor_id' => $doctor->id,
            'appointment_id' => $app8->id,
            'action' => 'revert',
            'queue_status' => 'checkin',
            'created_by' => $systemUser?->id,
            'created_at' => Carbon::parse($today->toDateString() . ' 13:58:00', $appTimezone),
        ]);
        AppointmentQueueLog::create([
            'doctor_id' => $doctor->id,
            'appointment_id' => $app8->id,
            'action' => 'start',
            'queue_status' => 'started',
            'started_at' => Carbon::parse($today->toDateString() . ' 14:40:00', $appTimezone),
            'created_by' => $systemUser?->id,
            'created_at' => Carbon::parse($today->toDateString() . ' 14:40:00', $appTimezone),
        ]);
        AppointmentQueueLog::create([
            'doctor_id' => $doctor->id,
            'appointment_id' => $app8->id,
            'action' => 'complete',
            'queue_status' => 'completed',
            'started_at' => Carbon::parse($today->toDateString() . ' 14:40:00', $appTimezone),
            'ended_at' => Carbon::parse($today->toDateString() . ' 15:20:00', $appTimezone),
            'duration_seconds' => 2400,
            'remarks' => 'Completed Today Shift 2 (20 mins extra time worked!)',
            'created_by' => $systemUser?->id,
            'created_at' => Carbon::parse($today->toDateString() . ' 15:20:00', $appTimezone),
        ]);

        // Break 3 Start: 15:21:00, End: 15:22:00 (1 min break example today)
        AppointmentQueueLog::create([
            'doctor_id' => $doctor->id,
            'action' => 'break_start',
            'created_by' => $systemUser?->id,
            'created_at' => Carbon::parse($today->toDateString() . ' 15:21:00', $appTimezone),
        ]);
        AppointmentQueueLog::create([
            'doctor_id' => $doctor->id,
            'action' => 'break_end',
            'created_by' => $systemUser?->id,
            'created_at' => Carbon::parse($today->toDateString() . ' 15:22:00', $appTimezone),
        ]);

        // Today Shift 3: 17:00 - 19:00
        DoctorAvailability::create([
            'doctor_id' => $doctor->id,
            'date' => $today->toDateString(),
            'start_time' => '17:00',
            'end_time' => '19:00',
            'capacity' => 10,
            'consultation_type' => 'in-person',
            'opd_type' => 'general',
            'is_available' => true,
        ]);

        // Today Shift 3 doctor check-in: 17:10:00 (10 mins late)
        AppointmentQueueLog::create([
            'doctor_id' => $doctor->id,
            'action' => 'check_in',
            'created_by' => $systemUser?->id,
            'created_at' => Carbon::parse($today->toDateString() . ' 17:10:00', $appTimezone),
        ]);

        // Today Appointment 9: Amit, booked 17:00
        $app9T = Appointment::create([
            'id' => Str::uuid(),
            'patient_id' => $getPatient(8)->id,
            'doctor_id' => $doctor->id,
            'appointment_date' => $today->toDateString(),
            'appointment_time' => '17:00:00',
            'appointment_end_time' => '17:15:00',
            'consultation_type' => 'in-person',
            'status' => 'completed',
            'queue_status' => 'completed',
            'queue_number' => 'TOK-209',
            'slug' => 'amit-sharma-today-tok-209',
        ]);
        AppointmentQueueLog::create([
            'doctor_id' => $doctor->id,
            'appointment_id' => $app9T->id,
            'action' => 'revert',
            'queue_status' => 'checkin',
            'created_by' => $systemUser?->id,
            'created_at' => Carbon::parse($today->toDateString() . ' 17:12:00', $appTimezone),
        ]);
        AppointmentQueueLog::create([
            'doctor_id' => $doctor->id,
            'appointment_id' => $app9T->id,
            'action' => 'start',
            'queue_status' => 'started',
            'started_at' => Carbon::parse($today->toDateString() . ' 17:15:00', $appTimezone),
            'created_by' => $systemUser?->id,
            'created_at' => Carbon::parse($today->toDateString() . ' 17:15:00', $appTimezone),
        ]);
        AppointmentQueueLog::create([
            'doctor_id' => $doctor->id,
            'appointment_id' => $app9T->id,
            'action' => 'complete',
            'queue_status' => 'completed',
            'started_at' => Carbon::parse($today->toDateString() . ' 17:15:00', $appTimezone),
            'ended_at' => Carbon::parse($today->toDateString() . ' 17:45:00', $appTimezone),
            'duration_seconds' => 1800,
            'remarks' => 'Completed today evening shift patient 1',
            'created_by' => $systemUser?->id,
            'created_at' => Carbon::parse($today->toDateString() . ' 17:45:00', $appTimezone),
        ]);

        // Today Appointment 10: Seema, booked 17:30
        $app10T = Appointment::create([
            'id' => Str::uuid(),
            'patient_id' => $getPatient(9)->id,
            'doctor_id' => $doctor->id,
            'appointment_date' => $today->toDateString(),
            'appointment_time' => '17:30:00',
            'appointment_end_time' => '17:45:00',
            'consultation_type' => 'in-person',
            'status' => 'completed',
            'queue_status' => 'completed',
            'queue_number' => 'TOK-210',
            'slug' => 'amit-sharma-today-tok-210',
        ]);
        AppointmentQueueLog::create([
            'doctor_id' => $doctor->id,
            'appointment_id' => $app10T->id,
            'action' => 'revert',
            'queue_status' => 'checkin',
            'created_by' => $systemUser?->id,
            'created_at' => Carbon::parse($today->toDateString() . ' 17:28:00', $appTimezone),
        ]);
        AppointmentQueueLog::create([
            'doctor_id' => $doctor->id,
            'appointment_id' => $app10T->id,
            'action' => 'start',
            'queue_status' => 'started',
            'started_at' => Carbon::parse($today->toDateString() . ' 17:50:00', $appTimezone),
            'created_by' => $systemUser?->id,
            'created_at' => Carbon::parse($today->toDateString() . ' 17:50:00', $appTimezone),
        ]);
        AppointmentQueueLog::create([
            'doctor_id' => $doctor->id,
            'appointment_id' => $app10T->id,
            'action' => 'complete',
            'queue_status' => 'completed',
            'started_at' => Carbon::parse($today->toDateString() . ' 17:50:00', $appTimezone),
            'ended_at' => Carbon::parse($today->toDateString() . ' 18:20:00', $appTimezone),
            'duration_seconds' => 1800,
            'remarks' => 'Completed today evening shift patient 2',
            'created_by' => $systemUser?->id,
            'created_at' => Carbon::parse($today->toDateString() . ' 18:20:00', $appTimezone),
        ]);

        // Today Break 4: 18:25:00 to 18:30:00 (5 mins)
        AppointmentQueueLog::create([
            'doctor_id' => $doctor->id,
            'action' => 'break_start',
            'created_by' => $systemUser?->id,
            'created_at' => Carbon::parse($today->toDateString() . ' 18:25:00', $appTimezone),
        ]);
        AppointmentQueueLog::create([
            'doctor_id' => $doctor->id,
            'action' => 'break_end',
            'created_by' => $systemUser?->id,
            'created_at' => Carbon::parse($today->toDateString() . ' 18:30:00', $appTimezone),
        ]);

        // Today Appointment 11: Vikas, booked 18:30
        $app11T = Appointment::create([
            'id' => Str::uuid(),
            'patient_id' => $getPatient(10)->id,
            'doctor_id' => $doctor->id,
            'appointment_date' => $today->toDateString(),
            'appointment_time' => '18:30:00',
            'appointment_end_time' => '18:45:00',
            'consultation_type' => 'in-person',
            'status' => 'completed',
            'queue_status' => 'completed',
            'queue_number' => 'TOK-211',
            'slug' => 'amit-sharma-today-tok-211',
        ]);
        AppointmentQueueLog::create([
            'doctor_id' => $doctor->id,
            'appointment_id' => $app11T->id,
            'action' => 'revert',
            'queue_status' => 'checkin',
            'created_by' => $systemUser?->id,
            'created_at' => Carbon::parse($today->toDateString() . ' 18:32:00', $appTimezone),
        ]);
        AppointmentQueueLog::create([
            'doctor_id' => $doctor->id,
            'appointment_id' => $app11T->id,
            'action' => 'start',
            'queue_status' => 'started',
            'started_at' => Carbon::parse($today->toDateString() . ' 18:35:00', $appTimezone),
            'created_by' => $systemUser?->id,
            'created_at' => Carbon::parse($today->toDateString() . ' 18:35:00', $appTimezone),
        ]);
        AppointmentQueueLog::create([
            'doctor_id' => $doctor->id,
            'appointment_id' => $app11T->id,
            'action' => 'complete',
            'queue_status' => 'completed',
            'started_at' => Carbon::parse($today->toDateString() . ' 18:35:00', $appTimezone),
            'ended_at' => Carbon::parse($today->toDateString() . ' 19:05:00', $appTimezone),
            'duration_seconds' => 1800,
            'remarks' => 'Completed today evening shift (5 mins extra time worked!)',
            'created_by' => $systemUser?->id,
            'created_at' => Carbon::parse($today->toDateString() . ' 19:05:00', $appTimezone),
        ]);


        // ==========================================
        // SCENARIO 3: TOMORROW (FUTURE SCHEDULING)
        // ==========================================
        // Create shifts for tomorrow
        DoctorAvailability::create([
            'doctor_id' => $doctor->id,
            'date' => $tomorrow->toDateString(),
            'start_time' => '09:00',
            'end_time' => '11:00',
            'capacity' => 10,
            'consultation_type' => 'in-person',
            'opd_type' => 'general',
            'is_available' => true,
        ]);
        DoctorAvailability::create([
            'doctor_id' => $doctor->id,
            'date' => $tomorrow->toDateString(),
            'start_time' => '13:00',
            'end_time' => '15:00',
            'capacity' => 10,
            'consultation_type' => 'in-person',
            'opd_type' => 'general',
            'is_available' => true,
        ]);
        DoctorAvailability::create([
            'doctor_id' => $doctor->id,
            'date' => $tomorrow->toDateString(),
            'start_time' => '17:00',
            'end_time' => '19:00',
            'capacity' => 10,
            'consultation_type' => 'in-person',
            'opd_type' => 'general',
            'is_available' => true,
        ]);

        // Tomorrow Appointment 1
        Appointment::create([
            'id' => Str::uuid(),
            'patient_id' => $getPatient(0)->id,
            'doctor_id' => $doctor->id,
            'appointment_date' => $tomorrow->toDateString(),
            'appointment_time' => '09:00:00',
            'appointment_end_time' => '09:15:00',
            'consultation_type' => 'in-person',
            'status' => 'confirmed',
            'queue_status' => 'scheduled',
            'queue_number' => 'TOK-101',
            'slug' => 'amit-sharma-tomorrow-tok-101',
        ]);
        // Tomorrow Appointment 2
        Appointment::create([
            'id' => Str::uuid(),
            'patient_id' => $getPatient(1)->id,
            'doctor_id' => $doctor->id,
            'appointment_date' => $tomorrow->toDateString(),
            'appointment_time' => '09:15:00',
            'appointment_end_time' => '09:30:00',
            'consultation_type' => 'in-person',
            'status' => 'confirmed',
            'queue_status' => 'scheduled',
            'queue_number' => 'TOK-102',
            'slug' => 'amit-sharma-tomorrow-tok-102',
        ]);
        // Tomorrow Appointment 9
        Appointment::create([
            'id' => Str::uuid(),
            'patient_id' => $getPatient(8)->id,
            'doctor_id' => $doctor->id,
            'appointment_date' => $tomorrow->toDateString(),
            'appointment_time' => '17:00:00',
            'appointment_end_time' => '17:15:00',
            'consultation_type' => 'in-person',
            'status' => 'confirmed',
            'queue_status' => 'scheduled',
            'queue_number' => 'TOK-109',
            'slug' => 'amit-sharma-tomorrow-tok-109',
        ]);
    }
}
