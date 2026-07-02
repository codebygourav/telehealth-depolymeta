<?php

namespace App\Http\Controllers\Api\V2\Doctor;

use App\Http\Controllers\Controller;
use App\Http\Resources\Patient\PatientDetailResource;
use App\Models\{Appointment, Patient, MedicalReport, Prescription};
use App\Services\ApiResponseService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class PatientBrowserController extends Controller
{
    public function show(Request $request, Appointment $appointment)
    {
        $user = $request->user();

        // Verify doctor
        if (!$user->doctor) {
            return ApiResponseService::unauthorized();
        }

        $doctor = $user->doctor;
        $today = Carbon::today();

        // Ensure appointment belongs to this doctor
        if ($appointment->doctor_id !== $doctor->id) {
            return ApiResponseService::unauthorized();
        }

        // Load patient
        $patient = Patient::where('id', $appointment->patient_id)
            ->first();

        if (! $patient) {
            return ApiResponseService::notFound();
        }

        // Upcoming appointments
        $upcomingAppointments = Appointment::where('id', $appointment->id)
            ->whereNotIn('status', ['cancelled', 'completed'])
            ->with(['doctor.departments', 'doctor.user', 'videoConsultation'])
            ->get();

        // Previous appointments
        // Fetch total number of appointments between the doctor and patient
        $totalAppointments = Appointment::where('doctor_id', $doctor->id)
            ->where('patient_id', $patient->id)
            ->count();

        // Only fetch previous appointments if there is more than one
        if ($totalAppointments > 1) {
            $previousAppointments = Appointment::where('doctor_id', $doctor->id)
                ->where('patient_id', $patient->id)
                ->where(function ($q) use ($today, $appointment) {
                    // Only include the current appointment if it is in the past (before today or completed/cancelled)
                    $q->where(function ($query) use ($today) {
                        $query->whereDate('appointment_date', '<', $today)
                            ->orWhereIn('status', ['cancelled', 'completed']);
                    });
                })
                ->with(['doctor.departments', 'doctor.user', 'videoConsultation'])
                ->orderByDesc('appointment_date')
                ->orderByDesc('appointment_time')
                ->limit(10)
                ->get();
        } else {
            // If only one appointment, don't include it in previous appointments
            $previousAppointments = collect();
        }

        // Medical reports - fetch only those which the patient shared with the doctor (is_public = true) and for this appointment
        $medicalReports = MedicalReport::where('patient_id', $patient->id)
            ->where('appointment_id', $appointment->id)
            ->with('doctor')
            ->orderByDesc('report_date')
            ->limit(10)
            ->get();

        // Current medications
        $currentMedications = Prescription::where('patient_id', $patient->id)
            ->where(function ($q) {
                $q->where('is_ongoing', true)
                    ->orWhere(function ($q2) {
                        $q2->where('is_ongoing', false)
                            ->whereNotNull('end_date')
                            ->where('end_date', '>=', Carbon::today());
                    });
            })
            ->with(['appointment', 'doctor'])
            ->orderByDesc('start_date')
            ->get();

        // All appointments (for problem extraction)
        $allAppointments = Appointment::where('patient_id', $patient->id)
            ->orderByDesc('appointment_date')
            ->orderByDesc('appointment_time')
            ->get();

        // Attach relations
        $patient->setRelation('appointments', $allAppointments);
        $patient->setRelation('upcomingAppointments', $upcomingAppointments);
        $patient->setRelation('previousAppointments', $previousAppointments);
        $patient->setRelation('medicalReports', $medicalReports);
        $patient->setRelation('currentMedications', $currentMedications);
        $patient->setRelation('currentAppointment', $appointment);
        $patient->load('user');

        return ApiResponseService::success(
            responseKey: 'responses.success',
            data: (new PatientDetailResource($patient))->toArray(request())
        );
    }
}
