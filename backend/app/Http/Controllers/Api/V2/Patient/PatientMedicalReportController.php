<?php

namespace App\Http\Controllers\Api\V2\Patient;

use App\Http\Controllers\Controller;
use App\Enums\MedicalReportStatus;
use App\Http\Resources\Common\MedicalReportResource;
use App\Services\ApiResponseService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use App\Models\{Patient, MedicalReport, Appointment};
use Illuminate\Support\Facades\Hash;


class PatientMedicalReportController extends Controller
{
    public function index(Request $request, string $user_id)
    {
        $user = $request->user();

        if (! $user) {
            return ApiResponseService::unauthenticated();
        }

        $canAccess = false;

        // 1. Check if the user is the patient themselves
        if ($user->patient && $user->id === $user_id) {
            $canAccess = true;
        }
        // 2. Check if the user is a doctor who has an appointment with this patient
        elseif ($user->doctor) {
            $targetPatient = Patient::where('user_id', $user_id)->first();
            if ($targetPatient) {
                $hasAppointment = Appointment::where('doctor_id', $user->doctor->id)
                    ->where('patient_id', $targetPatient->id)
                    ->exists();
                if ($hasAppointment) {
                    $canAccess = true;
                }
            }
        }

        if (! $canAccess) {
            return ApiResponseService::unauthorized();
        }

        $patient = Patient::where('user_id', $user_id)->first();
        if (! $patient) {
            return ApiResponseService::notFound('Patient not found');
        }

        $reports = MedicalReport::where('patient_id', $patient->id)
            ->orderBy('report_date', 'desc')
            ->paginate(20);

        $reports->setCollection(
            MedicalReportResource::collection($reports->items())->collection
        );

        return ApiResponseService::paginated(
            $reports,
            responseKey: 'fetched'
        );
    }

    public function store(Request $request, string $user_id)
    {
        try {
            $user = $request->user();

            if (! $user) {
                return ApiResponseService::unauthenticated();
            }

            // Authorization: User must be the patient themselves
            if (! $user->patient || $user->id !== $user_id) {
                return ApiResponseService::unauthorized();
            }

            $patient = $user->patient;
            if (! $patient) {
                return ApiResponseService::notFound('Patient profile not found');
            }

            try {
                $validated = $request->validate([
                    'name' => 'required|string|max:255',
                    'type' => 'required|string',
                    'file' => 'required|file|mimes:pdf,jpg,jpeg,png,webp,ppt,pptx,doc,docx,txt,xls,xlsx|max:10240',
                    'is_public' => 'sometimes|boolean',
                ]);
            } catch (ValidationException $ex) {
                return ApiResponseService::validationError($ex->errors());
            }

            $report = new MedicalReport;
            $report->patient_id = $patient->id;
            $report->name = $request->name;
            $report->type = $request->type;
            $report->report_date = now()->toDateString();
            $report->is_public = $request->boolean('is_public');
            $report->is_shared = false;
            $report->status = MedicalReportStatus::UPLOADED;

            if ($request->hasFile('file')) {
                $file = $request->file('file');
                $filename = Str::uuid() . '.' . $file->getClientOriginalExtension();
                $path = $file->storeAs('medical_report', $filename, 'public');

                $report->file = $path; // Set for InteractsWithModuleDocuments trait
                $report->file_path = $path;
                $report->file_name = $file->getClientOriginalName();
                $report->file_type = $file->getClientOriginalExtension();
            } else {
                return ApiResponseService::error('File is required', ['message' => 'File is required'], 422);
            }

            $report->save();

            return ApiResponseService::success(
                responseKey: 'responses.created',
                data: [
                    'id' => $report->id,
                    'file_url' => $report->file_url,
                ]
            );
        } catch (Exception $e) {
            return ApiResponseService::serverError($e);
        }
    }

    /**
     * Delete a patient's medical report association with an appointment (unshare)
     */
    public function medicalReportDeleteForAppointment(Request $request, string $appointmentId, string $reportId)
    {
        try {
            $user = $request->user();

            if (! $user) {
                return ApiResponseService::unauthenticated();
            }

            // Authorization: Only the patient themselves can manage their reports
            if (!$user->patient) {
                return ApiResponseService::unauthorized();
            }

            $patient = $user->patient;

            $report = MedicalReport::where('id', $reportId)
                ->where('patient_id', $patient->id)
                ->where('appointment_id', $appointmentId)
                ->first();

            if (! $report) {
                return ApiResponseService::notFound('Medical report association with this appointment not found');
            }

            // Unshare the report: Remove appointment and doctor associations
            $report->appointment_id = null;
            $report->doctor_id = null;
            $report->is_shared = false;
            $report->status = MedicalReportStatus::UPLOADED;
            $report->save();

            return ApiResponseService::success(
                data: 'Medical report unshared successfully',
            );
        } catch (Exception $e) {
            return ApiResponseService::serverError($e);
        }
    }

    /**
     * Delete a patient's medical report
     */
    public function destroy(Request $request, string $reportId)
    {
        try {
            $user = $request->user();

            if (! $user) {
                return ApiResponseService::unauthenticated();
            }

            // Authorization: Only the patient themselves can delete their reports
            if (!$user->patient) {
                return ApiResponseService::validationError('You are not authorized to delete this report.');
            }

            $patient = $user->patient;
            if (! $patient) {
                return ApiResponseService::notFound('Patient profile not found');
            }

            $report = MedicalReport::where('id', $reportId)
                ->where('patient_id', $patient->id)
                ->first();

            if (! $report) {
                return ApiResponseService::notFound('Medical report not found');
            }

            $report->delete();

            return ApiResponseService::success(
                data: 'Medical report deleted successfully',
            );
        } catch (Exception $e) {
            return ApiResponseService::serverError($e);
        }
    }
}
