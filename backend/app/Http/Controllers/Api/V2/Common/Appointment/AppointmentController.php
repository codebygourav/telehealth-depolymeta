<?php

namespace App\Http\Controllers\Api\V2\Common\Appointment;

use App\Http\Controllers\Controller;
use App\Enums\{AppointmentStatus, MedicalReportStatus};
use App\Http\Resources\Common\{AppointmentDetailResource, AppointmentResource};
use App\Models\{Appointment, DoctorAvailability, MedicalReport, Patient, Doctor};
use App\Services\{ApiResponseService, WherebyService, NotificationService};
use Carbon\Carbon;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{Cache, Log};
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;



class AppointmentController extends Controller
{
    public function myAppointments(Request $request)
    {
        $user = $request->user();
        $filter = $request->get('filter', 'today');
        $perPage = $request->get('per_page', 10);

        $isPatient = $user->patient;
        $isDoctor = $user->doctor;

        if (! $isPatient && ! $isDoctor) {
            return ApiResponseService::notFound();
        }

        $query = Appointment::query()->withoutTestDoctors();

        if ($isPatient) {
            $query->where('appointments.patient_id', $user->patient->id);
        } else {
            $query->where('appointments.doctor_id', $user->doctor->id);
        }

        $query->with([
            'doctor' => function ($q) {
                $q->with(['departments', 'user'])
                    ->withAvg('reviews', 'rating'); // ⭐ THIS LINE
            },
            'patient.user',
            'videoConsultation',
            'patient',
            'availability',
        ]);

        $today = Carbon::today();
        $dateTimeExpression = \Illuminate\Support\Facades\DB::getDriverName() === 'sqlite'
            ? "datetime(appointments.appointment_date || ' ' || appointments.appointment_end_time)"
            : "STR_TO_DATE(CONCAT(appointments.appointment_date,' ',appointments.appointment_end_time), '%Y-%m-%d %H:%i:%s')";

        switch ($filter) {
            case 'today':
                $query->whereDate('appointments.appointment_date', Carbon::today())
                    ->whereIn('appointments.status', [
                        AppointmentStatus::CONFIRMED->value,
                        AppointmentStatus::RESCHEDULED->value,
                        AppointmentStatus::CANCELLED->value,
                        AppointmentStatus::COMPLETED->value,
                    ])
                    ->whereRaw(
                        "{$dateTimeExpression} >= ?",
                        [Carbon::now()->format('Y-m-d H:i:s')]
                    )
                    ->orderBy('appointments.appointment_time', 'asc');
                break;

            case 'upcoming':
                $query->whereRaw(
                    "{$dateTimeExpression} > ?",
                    [Carbon::now()->format('Y-m-d H:i:s')]
                )
                    ->whereIn('appointments.status', [
                        AppointmentStatus::CONFIRMED->value,
                        AppointmentStatus::RESCHEDULED->value,
                        AppointmentStatus::CANCELLED->value,
                        AppointmentStatus::COMPLETED->value,
                    ])
                    ->orderBy('appointments.appointment_date', 'asc')
                    ->orderBy('appointments.appointment_time', 'asc');
                break;

            case 'past':
                $query->whereRaw(
                    "{$dateTimeExpression} < ?",
                    [Carbon::now()->format('Y-m-d H:i:s')]
                )
                    ->whereIn('appointments.status', [
                        AppointmentStatus::COMPLETED->value,
                        AppointmentStatus::CANCELLED->value,
                        AppointmentStatus::FAILED->value,
                        AppointmentStatus::RESCHEDULED->value,
                        AppointmentStatus::CONFIRMED->value,
                    ])
                    ->orderBy('appointments.appointment_date', 'desc')
                    ->orderBy('appointments.appointment_time', 'desc')
                    ->distinct('appointments.id');
                break;

            case 'all':
                $query->whereIn('appointments.status', [
                    AppointmentStatus::COMPLETED->value,
                    AppointmentStatus::CANCELLED->value,
                    AppointmentStatus::FAILED->value,
                    AppointmentStatus::RESCHEDULED->value,
                    AppointmentStatus::CONFIRMED->value,
                ])
                    ->orderBy('appointments.appointment_date', 'desc')
                    ->orderBy('appointments.appointment_time', 'desc')
                    ->distinct('appointments.id');
                break;

            default:
                return null;
        }

        $appointments = $query->paginate($perPage);

        $appointments->setCollection(
            AppointmentResource::collection($appointments->getCollection())->collection
        );

        return ApiResponseService::paginated(
            paginated: $appointments,
            responseKey: 'responses.success',
            extra: ['filter' => $filter],
        );
    }

    public function show(Request $request, string $id)
    {
        $user = $request->user();

        $appointment = Appointment::with([
            'patient.user',
            'doctor.user',
            'doctor.departments',
            'doctor.reviews',
            'prescriptions.medicine',
            'medicalReports.doctor',
            'payment',
            'videoConsultation',
            'doctorReviews',
        ])->where('id', $id)->firstOrFail();

        if ((bool) $appointment->doctor?->is_test_doctor) {
            return ApiResponseService::notFound(resource: 'Appointment', module: 'appointment');
        }


        $isPatientOwner = $this->isPatientOwner($user, $appointment);
        $isDoctorOwner = $this->isDoctorOwner($user, $appointment);

        if (! $isPatientOwner && ! $isDoctorOwner) {
            return ApiResponseService::unauthorized();
        }

        return ApiResponseService::success(
            responseKey: 'responses.success',
            data: new AppointmentDetailResource($appointment)
        );
    }

    public function markAsCompleted(Request $request, string $appointmentId)
    {
        $user = $request->user();
        /**
         * ✅ Only doctor role allowed
         */
        if (! $user->doctor) {
            return ApiResponseService::unauthorized();
        }

        $validator = Validator::make($request->all(), [
            'is_complete' => ['required', 'boolean'],
        ]);

        if ($validator->fails()) {
            return ApiResponseService::validationError($validator->errors());
        }

        $data = $validator->validated();

        // If doctor unchecked checkbox, do nothing
        if ($data['is_complete'] !== true) {
            return ApiResponseService::validationError('Checkbox must be true to complete appointment.');
        }

        $appointment = Appointment::findOrFail($appointmentId);

        /**
         * ✅ Ensure logged doctor owns this appointment
         */
        if (! $user->doctor || $appointment->doctor_id !== $user->doctor->id) {
            return ApiResponseService::unauthorized();
        }

        /**
         * ✅ Already completed guard
         */
        if (AppointmentStatus::equals($appointment->status, AppointmentStatus::COMPLETED)) {
            return ApiResponseService::success(
                'responses.success',
                data: [
                    'appointment_id' => $appointment->id,
                    'status' => $appointment->status,
                    'is_complete' => true,
                ]
            );
        }

        $appointment->update([
            'status' => AppointmentStatus::COMPLETED->value,
        ]);

        if ($appointment->videoConsultation) {
            $appointment->videoConsultation->update([
                'status' => AppointmentStatus::COMPLETED->value,
            ]);
        }

        NotificationService::notifyAppointmentCompleted($appointment);

        return ApiResponseService::success(
            responseKey: 'responses.appointment.updated',
            data: [
                'appointment_id' => $appointment->id,
                'status' => $appointment->status,
                'is_complete' => true, // 👈 virtual flag for frontend
            ]
        );
    }


    public function updateNotesAndReport(Request $request, string $appointmentId)
    {
        $user = $request->user();

        $appointment = Appointment::findOrFail($appointmentId);

        $isPatientOwner = $this->isPatientOwner($user, $appointment);
        $isDoctorOwner = $this->isDoctorOwner($user, $appointment);

        if (! $isPatientOwner && ! $isDoctorOwner) {
            return ApiResponseService::unauthorized();
        }

        $request->validate([
            'notes' => 'nullable|string',
            'reports' => 'nullable|array',
            'reports.*.id' => 'nullable|uuid',
            'reports.*.name' => 'required_with:reports|string|max:255',
            'reports.*.type' => 'required_with:reports|string|max:255',
            'reports.*.file' => 'nullable|file|mimes:pdf,jpg,jpeg,png,webp|max:5120',
        ]);


        if (
            !$request->filled('notes') &&
            !$request->filled('reports') &&
            !$request->hasFile('file') &&
            !$request->hasFile('files')
        ) {
            return ApiResponseService::validationError('At least one of notes or reports/files is required.');
        }


        try {
            if ($request->has('notes')) {

                $noteText = $request->input('notes');

                // Always overwrite existing notes
                $appointment->notes = [$noteText];

                $appointment->save();
            }
            if ($request->filled('reports')) {

                foreach ($request->reports as $index => $reportInput) {

                    $reportId = $reportInput['id'] ?? null;
                    $name = $reportInput['name'] ?? null;
                    $type = $reportInput['type'] ?? null;

                    $file = $request->file("reports.$index.file");

                    if ($reportId) {

                        $report = MedicalReport::where('id', $reportId)
                            ->first();

                        if (!$report) {
                            return ApiResponseService::validationError(
                                "Medical report with ID '{$reportId}' not found for this appointment."
                            );
                        }

                        // Update basic fields
                        $report->appointment_id = $appointment->id;
                        $report->doctor_id = $appointment->doctor_id;
                        $report->patient_id = $appointment->patient_id;

                        $report->name = $name ?? $report->name;
                        $report->type = $type ?? $report->type;

                        // ✅ If new file uploaded → replace old file
                        if ($file) {

                            if ($report->file_path && Storage::disk('public')->exists($report->file_path)) {
                                Storage::disk('public')->delete($report->file_path);
                            }

                            $extension = strtolower($file->getClientOriginalExtension());
                            $storedFilename = Str::uuid() . '.' . $extension;
                            $path = $file->storeAs('medical_report', $storedFilename, 'public');

                            $report->file_path = $path;
                            $report->file_name = $file->getClientOriginalName();
                            $report->file_type = $extension;
                        }

                        $report->is_shared = true;
                        $report->status = MedicalReportStatus::SHARED;

                        $report->save();
                    } else {

                        // New report → file required
                        if (!$file) {
                            return ApiResponseService::validationError(
                                "File is required for new report."
                            );
                        }

                        $extension = strtolower($file->getClientOriginalExtension());
                        $storedFilename = Str::uuid() . '.' . $extension;
                        $path = $file->storeAs('medical_report', $storedFilename, 'public');

                        MedicalReport::create([
                            'appointment_id' => $appointment->id,
                            'patient_id' => $appointment->patient_id,
                            'doctor_id' => $appointment->doctor_id,
                            'name' => $name,
                            'type' => $type,
                            'report_date' => now()->toDateString(),
                            'status' => MedicalReportStatus::SHARED,
                            'is_shared' => true,
                            'file_path' => $path,
                            'file_name' => $file->getClientOriginalName(),
                            'file_type' => $extension,
                            'uploader_id' => $appointment->patient_id,
                            'uploader_type' => 'Patient',
                        ]);
                    }
                }
            }

            $filesToProcess = [];

            if ($request->hasFile('file')) {
                $filesToProcess[] = $request->file('file');
            }

            if ($request->hasFile('files')) {
                $filesToProcess = array_merge($filesToProcess, $request->file('files'));
            }

            foreach ($filesToProcess as $file) {

                $filename = $file->getClientOriginalName();
                $name = pathinfo($filename, PATHINFO_FILENAME);

                $existingReport = MedicalReport::where('appointment_id', $appointment->id)
                    ->where('name', $name)
                    ->first();

                if ($existingReport) {
                    continue;
                }

                $extension = strtolower($file->getClientOriginalExtension());

                $type = match ($extension) {
                    'pdf' => 'lab_report',
                    'jpg', 'jpeg', 'png', 'webp' => 'radiology',
                    default => 'other'
                };

                $storedFilename = Str::uuid() . '.' . $extension;
                $path = $file->storeAs('medical_report', $storedFilename, 'public');

                MedicalReport::create([
                    'appointment_id' => $appointment->id,
                    'patient_id' => $appointment->patient_id,
                    'doctor_id' => $appointment->doctor_id,
                    'name' => $name,
                    'type' => $type,
                    'report_date' => now()->toDateString(),
                    'status' => MedicalReportStatus::SHARED,
                    'is_shared' => true,
                    'file_path' => $path,
                    'file_name' => $file->getClientOriginalName(),
                    'file_type' => $extension,
                ]);
            }

            $appointment->refresh();
            $appointment->load('medicalReports');

            return ApiResponseService::success(
                responseKey: 'responses.success',
                data: [
                    'message' => 'Appointment updated successfully',
                    'appointment_id' => $appointment->id,
                    'doctor_id' => $appointment->doctor_id,
                    'notes' => $appointment->notes,
                ]
            );
        } catch (\Exception $e) {
            return ApiResponseService::serverError($e);
        }
    }


    public function storeDoctorInstructions(Request $request, $appointmentId)
    {
        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'instructions_by_doctor' => ['nullable', 'string'],
            'next_visit_date' => ['nullable', 'date'],
            'files' => 'nullable|array',
            'files.*' => 'file|mimes:pdf,jpg,jpeg,png,webp',
            'type' => ['nullable', 'string', 'in:medical-report,other'], // Acceptable types can be modified as needed
        ]);

        if ($validator->fails()) {
            return ApiResponseService::validationError($validator->errors());
        }

        $validated = $validator->validated();

        $appointment = Appointment::findOrFail($appointmentId);

        if (! $this->isDoctorOwner($user, $appointment)) {
            return ApiResponseService::unauthorized();
        }

        $appointment->update([
            'instructions_by_doctor' => $validated['instructions_by_doctor'] ?? $appointment->instructions_by_doctor,
            'next_visit_date' => $validated['next_visit_date'] ?? $appointment->next_visit_date,
        ]);

        $files = $request->file('files', []);
        // Set reportType to what doctor submitted in "type" field
        $reportType = $validated['type'] ?? 'other';

        if ($files) {
            foreach ($files as $file) {
                if (!$file) {
                    continue;
                }
                $extension = strtolower($file->getClientOriginalExtension());

                // Check if a report already exists for this file and appointment (by name and type)
                $existingReport = MedicalReport::where('appointment_id', $appointment->id)
                    ->where('type', $reportType)
                    ->where('file_name', $file->getClientOriginalName())
                    ->first();

                if ($existingReport) {
                    // Update the file with new upload and update dates, do not duplicate
                    $storedFilename = Str::uuid() . '.' . $extension;
                    $path = $file->storeAs('conclusion_report', $storedFilename, 'public');

                    $existingReport->update([
                        'file_path' => $path,
                        'file_type' => $extension,
                        'report_date' => now()->toDateString(),
                        'type' => $reportType,
                        'status' => MedicalReportStatus::SHARED,
                        'is_shared' => true,
                        'uploader_id' => $appointment->doctor->id,
                        'uploader_type' => 'Doctor',
                    ]);
                } else {
                    // Create new report entry
                    $storedFilename = Str::uuid() . '.' . $extension;
                    $path = $file->storeAs('conclusion_report', $storedFilename, 'public');

                    MedicalReport::create([
                        'appointment_id' => $appointment->id,
                        'patient_id' => $appointment->patient_id,
                        'doctor_id' => $appointment->doctor->id,
                        'name' => $file->getClientOriginalName(),
                        'type' => $reportType,
                        'report_date' => now()->toDateString(),
                        'status' => MedicalReportStatus::SHARED,
                        'is_shared' => true,
                        'file_path' => $path,
                        'file_name' => $file->getClientOriginalName(),
                        'file_type' => $extension,
                        'uploader_id' => $appointment->doctor->id,
                        'uploader_type' => 'Doctor',
                    ]);
                }
            }
        }

        NotificationService::notifyDoctorInstructionsAdded($appointment);

        return ApiResponseService::success(
            'responses.appointment.updated',
            data: [
                'appointment_id' => $appointment->id,
                'instructions_by_doctor' => $appointment->instructions_by_doctor,
                'next_visit_date' => $appointment->next_visit_date ? Carbon::parse($appointment->next_visit_date)->format('Y-m-d') : null,
            ]
        );
    }

    public function getDoctorInstructions(Request $request, $appointmentId)
    {
        $user = $request->user();
        $appointment = Appointment::findOrFail($appointmentId);

        $isPatientOwner = $this->isPatientOwner($user, $appointment);
        $isDoctorOwner = $this->isDoctorOwner($user, $appointment);

        if (! $isPatientOwner && ! $isDoctorOwner) {
            return ApiResponseService::unauthorized();
        }

        // Fetch all medical reports uploaded by the doctor for this appointment
        $medicalReports = $appointment->medicalReports()
            ->where('uploader_type', 'Doctor')
            ->orderByDesc('report_date')
            ->get()
            ->map(function ($report) {
                return [
                    'id' => $report->id,
                    'name' => $report->name,
                    'type' => $report->type,
                    'file_url' => $report->file_url,
                    'report_date' => $report->report_date,
                    'file_type' => $report->file_type,
                    'is_shared' => $report->is_shared,
                ];
            })->values();

        return ApiResponseService::success(
            'responses.appointment.fetched',
            data: [
                'appointment_id' => $appointment->id,
                'instructions_by_doctor' => $appointment->instructions_by_doctor,
                'next_visit_date' => $appointment->next_visit_date
                    ? Carbon::parse($appointment->next_visit_date)->format('Y-m-d')
                    : null,
                'conclusion_report_files' => $medicalReports,
            ]
        );
    }

    public function knockVideoCall(Request $request, string $appointmentId)
    {
        $user = $request->user();

        $appointment = Appointment::findOrFail($appointmentId);

        // Verify the user is the patient of this appointment
        if (! $this->isPatientOwner($user, $appointment)) {
            return ApiResponseService::unauthorized();
        }

        // Notify the doctor
        NotificationService::notifyPatientKnocks($appointment);

        return ApiResponseService::success(
            'responses.success',
            [],
            ['message' => 'Doctor notified successfully.']
        );
    }

    private function isPatientOwner($user, Appointment $appointment): bool
    {
        if (! $user) {
            return false;
        }

        if ($user->patient && $appointment->patient_id === $user->patient->id) {
            return true;
        }

        if (! $appointment->relationLoaded('patient')) {
            $appointment->loadMissing('patient');
        }

        return $appointment->patient?->user_id === $user->id;
    }

    private function isDoctorOwner($user, Appointment $appointment): bool
    {
        if (! $user) {
            return false;
        }

        if ($user->doctor && $appointment->doctor_id === $user->doctor->id) {
            return true;
        }

        if (! $appointment->relationLoaded('doctor')) {
            $appointment->loadMissing('doctor');
        }

        return $appointment->doctor?->user_id === $user->id;
    }
}
