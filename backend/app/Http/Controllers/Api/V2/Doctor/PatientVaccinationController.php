<?php

namespace App\Http\Controllers\Api\V2\Doctor;

use App\Enums\PatientProfileType;
use App\Enums\VaccinationDocumentType;
use App\Enums\VaccinationStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\Vaccination\PatientProfileResource;
use App\Http\Resources\Vaccination\PatientVaccinationProgramResource;
use App\Http\Resources\Vaccination\PatientVaccinationResource;
use App\Models\Patient;
use App\Models\PatientProfile;
use App\Models\PatientVaccination;
use App\Models\PatientVaccinationProgram;
use App\Models\Vaccination;
use App\Models\VaccinationDocument;
use App\Models\VaccinationTemplate;
use App\Services\ApiResponseService;
use App\Services\PatientVaccinationOverviewService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class PatientVaccinationController extends Controller
{
    public function index(Request $request, string $patientId)
    {
        $doctor = $request->user()?->doctor;
        if (! $doctor) {
            return ApiResponseService::unauthorized();
        }

        Patient::findOrFail($patientId);

        $validated = $request->validate([
            'status' => ['nullable', Rule::in(VaccinationStatus::values())],
        ]);

        $vaccinations = PatientVaccination::with(['vaccination', 'documents', 'template.program', 'patient.user', 'patientProfile'])
            ->where('patient_id', $patientId)
            ->where('doctor_id', $doctor->id)
            ->when($request->filled('patient_profile_id'), fn($query) => $query->where('patient_profile_id', $request->string('patient_profile_id')->toString()))
            ->when(! empty($validated['status']), fn($query) => $query->where('status', $validated['status']))
            ->orderBy('set_sort_order')
            ->orderByRaw('scheduled_date IS NULL, scheduled_date ASC')
            ->latest()
            ->paginate($request->integer('per_page', 10));

        return ApiResponseService::paginated($vaccinations->through(fn($vaccination) => new PatientVaccinationResource($vaccination)));
    }

    public function assignTemplate(Request $request, string $patientId)
    {
        $doctor = $request->user()?->doctor;
        if (! $doctor) {
            return ApiResponseService::unauthorized();
        }

        $patient = Patient::findOrFail($patientId);

        $data = $request->validate([
            'template_id' => ['required', 'exists:vaccination_templates,id'],
            'patient_profile_id' => ['nullable', 'exists:patient_profiles,id'],
            'first_dose_date' => ['nullable', 'date'],
            'start_date' => ['nullable', 'date'],
        ]);

        $template = VaccinationTemplate::with(['items.vaccination', 'program'])
            ->where('doctor_id', $doctor->id)
            ->where('is_active', true)
            ->findOrFail($data['template_id']);

        if (! $template->vaccination_program_id) {
            return ApiResponseService::validationError('Selected template must be linked to a vaccination program before assignment.');
        }

        $profile = $this->resolvePatientProfile($patient, $data['patient_profile_id'] ?? null);
        $firstDoseDateInput = $data['first_dose_date'] ?? $data['start_date'] ?? now()->toDateString();
        $firstDoseDate = Carbon::parse($firstDoseDateInput)->startOfDay();

        $assignment = DB::transaction(function () use ($template, $profile, $doctor, $firstDoseDate) {
            return PatientVaccinationProgram::create([
                'patient_profile_id' => $profile->id,
                'vaccination_program_id' => $template->vaccination_program_id,
                'vaccination_template_id' => $template->id,
                'doctor_id' => $doctor->id,
                'start_date' => $firstDoseDate->toDateString(),
                'status' => 'active',
            ]);
        });

        return ApiResponseService::created(
            data: new PatientVaccinationProgramResource($assignment->load([
                'patientProfile',
                'vaccinationProgram',
                'vaccinationTemplate.items.vaccination',
                'patientVaccinations.vaccination',
                'patientVaccinations.documents',
                'patientVaccinations.template.program',
                'patientVaccinations.patientProfile',
                'patientVaccinations.patient.user',
            ]))
        );
    }

    public function assignCustomVaccination(Request $request, string $patientId)
    {
        $doctor = $this->doctor($request);
        if (! $doctor) {
            return ApiResponseService::unauthorized();
        }

        Patient::findOrFail($patientId);

        $data = $request->validate([
            'patient_profile_id' => ['nullable', 'exists:patient_profiles,id'],
            'vaccination_id' => ['required', 'exists:vaccinations,id'],
            'dose_no' => ['nullable', 'integer', 'min:1'],
            'scheduled_date' => ['nullable', 'date'],
            'status' => ['nullable', Rule::in([VaccinationStatus::PENDING->value, VaccinationStatus::SCHEDULED->value])],
            'first_dose_date' => ['nullable', 'date'],
            'manufacturer' => ['nullable', 'string', 'max:255'],
            'given_at' => ['nullable', 'string', 'max:255'],
            'given_by' => ['nullable', 'string', 'max:255'],
            'doctor_notes' => ['nullable', 'string'],
        ]);

        $vaccination = Vaccination::findOrFail($data['vaccination_id']);
        $patient = Patient::findOrFail($patientId);
        $profile = $this->resolvePatientProfile($patient, $data['patient_profile_id'] ?? null);
        $status = $data['status'] ?? (! empty($data['scheduled_date']) ? VaccinationStatus::SCHEDULED : VaccinationStatus::PENDING);

        $patientVaccination = PatientVaccination::create([
            'patient_id' => $patientId,
            'patient_profile_id' => $profile->id,
            'doctor_id' => $doctor->id,
            'vaccination_id' => $vaccination->id,
            'dose_no' => $data['dose_no'] ?? 1,
            'first_dose_date' => $data['first_dose_date'] ?? $data['scheduled_date'] ?? null,
            'scheduled_date' => $data['scheduled_date'] ?? null,
            'status' => $status,
            'manufacturer' => $data['manufacturer'] ?? $vaccination->manufacturer,
            'given_at' => $data['given_at'] ?? null,
            'given_by' => $data['given_by'] ?? null,
            'doctor_notes' => $data['doctor_notes'] ?? null,
            'reminder_sent' => false,
            'next_reminder_at' => ! empty($data['scheduled_date']) ? Carbon::parse($data['scheduled_date'])->subDay() : null,
        ]);

        return ApiResponseService::created(
            data: new PatientVaccinationResource($patientVaccination->load(['vaccination', 'documents', 'template.program', 'patient.user', 'patientProfile']))
        );
    }

    public function update(Request $request, string $id)
    {
        $patientVaccination = $this->ownedPatientVaccination($request, $id);
        if (! $patientVaccination) {
            return ApiResponseService::unauthorized();
        }

        $data = $request->validate([
            'dose_no' => ['sometimes', 'integer', 'min:1'],
            'scheduled_date' => ['nullable', 'date'],
            'completed_date' => ['nullable', 'date'],
            'status' => ['sometimes', Rule::in(VaccinationStatus::values())],
            'batch_number' => ['nullable', 'string', 'max:255'],
            'manufacturer' => ['nullable', 'string', 'max:255'],
            'route' => ['nullable', 'string', 'max:255'],
            'site' => ['nullable', 'string', 'max:255'],
            'dose_amount' => ['nullable', 'string', 'max:255'],
            'given_at' => ['nullable', 'string', 'max:255'],
            'given_by' => ['nullable', 'string', 'max:255'],
            'doctor_notes' => ['nullable', 'string'],
            'side_effect_observed' => ['nullable', 'string'],
            'patient_reaction' => ['nullable', 'string'],
        ]);

        if (($data['status'] ?? null) === VaccinationStatus::COMPLETED->value && empty($data['completed_date'])) {
            $data['completed_date'] = now()->toDateString();
        }

        $patientVaccination->update($data);

        return ApiResponseService::success(
            data: new PatientVaccinationResource($patientVaccination->refresh()->load(['vaccination', 'documents', 'template.program', 'patient.user', 'patientProfile']))
        );
    }

    public function markCompleted(Request $request, string $id)
    {
        $patientVaccination = $this->ownedPatientVaccination($request, $id);
        if (! $patientVaccination) {
            return ApiResponseService::unauthorized();
        }

        $data = $request->validate([
            'completed_date' => ['nullable', 'date'],
            'batch_number' => ['nullable', 'string', 'max:255'],
            'manufacturer' => ['nullable', 'string', 'max:255'],
            'route' => ['nullable', 'string', 'max:255'],
            'site' => ['nullable', 'string', 'max:255'],
            'dose_amount' => ['nullable', 'string', 'max:255'],
            'given_at' => ['nullable', 'string', 'max:255'],
            'given_by' => ['nullable', 'string', 'max:255'],
            'doctor_notes' => ['nullable', 'string'],
            'side_effect_observed' => ['nullable', 'string'],
            'patient_reaction' => ['nullable', 'string'],
        ]);

        $patientVaccination->update(array_merge($data, [
            'status' => VaccinationStatus::COMPLETED,
            'completed_date' => $data['completed_date'] ?? now()->toDateString(),
        ]));

        return ApiResponseService::success(
            data: new PatientVaccinationResource($patientVaccination->refresh()->load(['vaccination', 'documents', 'template.program', 'patient.user', 'patientProfile']))
        );
    }

    public function addDocument(Request $request, string $id)
    {
        $patientVaccination = $this->ownedPatientVaccination($request, $id);
        if (! $patientVaccination) {
            return ApiResponseService::unauthorized();
        }

        $data = $request->validate([
            'document' => ['required', 'string', 'max:255'],
            'document_type' => ['sometimes', Rule::in(VaccinationDocumentType::values())],
            'certificate_number' => ['nullable', 'string', 'max:255'],
        ]);

        $patientVaccination->documents()->create([
            'document' => $data['document'],
            'document_type' => $data['document_type'] ?? VaccinationDocumentType::CERTIFICATE->value,
            'certificate_number' => $data['certificate_number'] ?? null,
        ]);

        return ApiResponseService::created(
            data: new PatientVaccinationResource($patientVaccination->refresh()->load(['vaccination', 'documents', 'template.program', 'patient.user', 'patientProfile']))
        );
    }

    public function deleteDocument(Request $request, string $documentId)
    {
        $doctor = $this->doctor($request);
        if (! $doctor) {
            return ApiResponseService::unauthorized();
        }

        $document = VaccinationDocument::whereHas('patientVaccination', fn($query) => $query->where('doctor_id', $doctor->id))
            ->findOrFail($documentId);

        $document->delete();

        return ApiResponseService::success();
    }

    public function patientProfiles(Request $request, string $patientId)
    {
        $doctor = $this->doctor($request);
        if (! $doctor) {
            return ApiResponseService::unauthorized();
        }

        $profiles = Patient::findOrFail($patientId)
            ->profiles()
            ->latest()
            ->paginate($request->integer('per_page', 10));

        return ApiResponseService::paginated($profiles->through(fn($profile) => new PatientProfileResource($profile)));
    }

    public function storePatientProfile(Request $request, string $patientId)
    {
        $doctor = $this->doctor($request);
        if (! $doctor) {
            return ApiResponseService::unauthorized();
        }

        $profile = Patient::findOrFail($patientId)
            ->profiles()
            ->create($this->validatedProfileData($request));

        return ApiResponseService::created(data: new PatientProfileResource($profile));
    }

    public function updatePatientProfile(Request $request, string $patientId, string $profileId)
    {
        $doctor = $this->doctor($request);
        if (! $doctor) {
            return ApiResponseService::unauthorized();
        }

        $profile = PatientProfile::where('patient_id', $patientId)->findOrFail($profileId);
        $profile->update($this->validatedProfileData($request, true));

        return ApiResponseService::success(data: new PatientProfileResource($profile->refresh()));
    }

    public function destroyPatientProfile(Request $request, string $patientId, string $profileId)
    {
        $doctor = $this->doctor($request);
        if (! $doctor) {
            return ApiResponseService::unauthorized();
        }

        PatientProfile::where('patient_id', $patientId)->findOrFail($profileId)->delete();

        return ApiResponseService::success();
    }

    public function patientVaccinations(Request $request, PatientVaccinationOverviewService $overviewService)
    {
        $patient = $request->user()?->patient;
        if (! $patient) {
            return ApiResponseService::unauthorized();
        }

        $validated = $request->validate([
            'patient_profile_id' => ['nullable', 'exists:patient_profiles,id'],
        ]);

        return ApiResponseService::success(
            data: $overviewService->build($patient, $validated['patient_profile_id'] ?? null)
        );
    }

    public function patientPrograms(Request $request)
    {
        $patient = $request->user()?->patient;
        if (! $patient) {
            return ApiResponseService::unauthorized();
        }

        $programs = PatientVaccinationProgram::with(['patientProfile', 'vaccinationProgram', 'vaccinationTemplate'])
            ->whereHas('patientProfile', fn($query) => $query->where('patient_id', $patient->id))
            ->when($request->filled('patient_profile_id'), fn($query) => $query->where('patient_profile_id', $request->string('patient_profile_id')->toString()))
            ->latest()
            ->paginate($request->integer('per_page', 10));

        return ApiResponseService::paginated($programs->through(fn($program) => new PatientVaccinationProgramResource($program)));
    }

    public function doctorPatientPrograms(Request $request, string $patientId)
    {
        $doctor = $this->doctor($request);
        if (! $doctor) {
            return ApiResponseService::unauthorized();
        }

        Patient::findOrFail($patientId);

        $programs = PatientVaccinationProgram::with(['patientProfile', 'vaccinationProgram', 'vaccinationTemplate'])
            ->where('doctor_id', $doctor->id)
            ->whereHas('patientProfile', fn($query) => $query->where('patient_id', $patientId))
            ->when($request->filled('patient_profile_id'), fn($query) => $query->where('patient_profile_id', $request->string('patient_profile_id')->toString()))
            ->latest()
            ->paginate($request->integer('per_page', 10));

        return ApiResponseService::paginated($programs->through(fn($program) => new PatientVaccinationProgramResource($program)));
    }

    public function patientShow(Request $request, string $id)
    {
        $patient = $request->user()?->patient;
        if (! $patient) {
            return ApiResponseService::unauthorized();
        }

        $vaccination = PatientVaccination::with(['vaccination.faqs', 'documents', 'template.program', 'patient.user', 'patientProfile'])
            ->where('patient_id', $patient->id)
            ->findOrFail($id);

        return ApiResponseService::success(
            data: new PatientVaccinationResource($vaccination)
        );
    }

    private function patientList(Request $request)
    {
        $patient = $request->user()?->patient;
        if (! $patient) {
            return ApiResponseService::unauthorized();
        }

        $validated = $request->validate([
            'filter' => ['nullable', Rule::in(['all', 'completed', 'upcoming'])],
            'patient_profile_id' => ['nullable', 'exists:patient_profiles,id'],
        ]);

        $filter = strtolower((string) ($validated['filter'] ?? 'all'));
        $statuses = null;
        $futureOnly = false;

        if ($filter === 'completed') {
            $statuses = [VaccinationStatus::COMPLETED->value];
        } elseif ($filter === 'upcoming') {
            $statuses = [VaccinationStatus::PENDING->value, VaccinationStatus::SCHEDULED->value];
            $futureOnly = true;
        }

        $vaccinations = PatientVaccination::with(['vaccination', 'documents', 'template.program', 'patient.user', 'patientProfile'])
            ->where('patient_id', $patient->id)
            ->when(! empty($validated['patient_profile_id']), fn($query) => $query->where('patient_profile_id', $validated['patient_profile_id']))
            ->when($statuses, fn($query) => $query->whereIn('status', $statuses))
            ->when($futureOnly, fn($query) => $query->whereDate('scheduled_date', '>=', now()->toDateString()))
            ->orderBy('set_sort_order')
            ->orderByRaw('scheduled_date IS NULL, scheduled_date ASC')
            ->latest()
            ->paginate($request->integer('per_page', 10));

        return ApiResponseService::paginated($vaccinations->through(fn($vaccination) => new PatientVaccinationResource($vaccination)));
    }

    private function ownedPatientVaccination(Request $request, string $id): ?PatientVaccination
    {
        $doctor = $this->doctor($request);
        if (! $doctor) {
            return null;
        }

        return PatientVaccination::with(['vaccination', 'documents', 'template.program', 'patient.user', 'patientProfile'])
            ->where('doctor_id', $doctor->id)
            ->where('id', $id)
            ->first();
    }

    private function doctor(Request $request)
    {
        return $request->user()?->doctor;
    }

    private function resolvePatientProfile(Patient $patient, ?string $profileId): PatientProfile
    {
        if ($profileId) {
            return PatientProfile::where('patient_id', $patient->id)->findOrFail($profileId);
        }

        $profile = $patient->profiles()->where('is_primary', true)->first()
            ?? $patient->profiles()->where('profile_type', PatientProfileType::SELF->value)->first();

        if ($profile) {
            return $profile;
        }

        return $patient->profiles()->create([
            'name' => trim("{$patient->first_name} {$patient->last_name}") ?: 'Self',
            'profile_type' => PatientProfileType::SELF->value,
            'date_of_birth' => $patient->date_of_birth,
            'gender' => in_array($patient->gender, ['male', 'female'], true) ? $patient->gender : null,
            'blood_group' => $patient->blood_group,
            'is_primary' => true,
        ]);
    }

    private function validatedProfileData(Request $request, bool $isUpdate = false): array
    {
        $required = $isUpdate ? 'sometimes' : 'required';

        return $request->validate([
            'name' => [$required, 'string', 'max:255'],
            'profile_type' => [$required, Rule::in(PatientProfileType::values())],
            'date_of_birth' => ['nullable', 'date'],
            'gender' => ['nullable', Rule::in(['male', 'female'])],
            'pregnancy_due_date' => ['nullable', 'date'],
            'blood_group' => ['nullable', 'string', 'max:20'],
            'weight' => ['nullable', 'numeric', 'min:0'],
            'height' => ['nullable', 'numeric', 'min:0'],
            'is_primary' => ['sometimes', 'boolean'],
        ]);
    }
}
