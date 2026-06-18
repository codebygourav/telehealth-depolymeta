<?php

namespace App\Http\Controllers\Api\V2\Doctor;

use App\Enums\VaccinationDocumentType;
use App\Enums\VaccinationStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\Vaccination\PatientVaccinationResource;
use App\Models\Patient;
use App\Models\PatientVaccination;
use App\Models\Vaccination;
use App\Models\VaccinationDocument;
use App\Models\VaccinationTemplate;
use App\Services\ApiResponseService;
use App\Services\PatientVaccinationOverviewService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Support\Str;

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
            'status' => ['nullable', Rule::in([...VaccinationStatus::values(), 'due', 'overdue'])],
            'filter' => ['nullable', Rule::in(['all', 'completed', 'upcoming'])],
            'search' => ['nullable', 'string', 'max:255'],
        ]);

        $statusFilter = $validated['status'] ?? null;
        $filter = strtolower((string) ($validated['filter'] ?? 'all'));
        $search = trim((string) ($validated['search'] ?? ''));
        $today = now()->toDateString();
        $upcomingStatuses = [
            VaccinationStatus::PENDING->value,
            VaccinationStatus::SCHEDULED->value,
            VaccinationStatus::UPCOMING->value,
            VaccinationStatus::DUE_SOON->value,
            VaccinationStatus::DUE_TODAY->value,
            VaccinationStatus::OVERDUE->value,
            VaccinationStatus::MISSED->value,
            VaccinationStatus::RESCHEDULED->value,
            VaccinationStatus::ON_HOLD->value,
            VaccinationStatus::SKIPPED_BY_DOCTOR->value,
        ];

        $vaccinations = PatientVaccination::with(['vaccination', 'documents', 'template.program', 'patient.user', 'logs.performedBy'])
            ->where('patient_id', $patientId)
            ->when($request->boolean('my_only'), fn($query) => $query->where('doctor_id', $doctor->id))
            ->when($filter === 'completed', fn($query) => $query->where('status', VaccinationStatus::COMPLETED->value))
            ->when($filter === 'upcoming', fn($query) => $query->whereIn('status', $upcomingStatuses))
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($subQuery) use ($search) {
                    $subQuery
                        ->where('set_name', 'like', "%{$search}%")
                        ->orWhere('recommended_age_label', 'like', "%{$search}%")
                        ->orWhereHas('vaccination', function ($vaccinationQuery) use ($search) {
                            $vaccinationQuery
                                ->where('name', 'like', "%{$search}%")
                                ->orWhere('short_name', 'like', "%{$search}%")
                                ->orWhere('manufacturer', 'like', "%{$search}%")
                                ->orWhere('disease_for', 'like', "%{$search}%");
                        });
                });
            })
            ->when($statusFilter && in_array($statusFilter, VaccinationStatus::values(), true), fn($query) => $query->where('status', $statusFilter))
            ->when($statusFilter === 'due', fn($query) => $query
                ->whereIn('status', [VaccinationStatus::PENDING->value, VaccinationStatus::SCHEDULED->value])
                ->whereDate('scheduled_date', $today))
            ->when($statusFilter === 'overdue', fn($query) => $query
                ->whereIn('status', [VaccinationStatus::PENDING->value, VaccinationStatus::SCHEDULED->value])
                ->whereDate('scheduled_date', '<', $today))
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
            'first_dose_date' => ['nullable', 'date'],
            'start_date' => ['nullable', 'date'],
        ]);

        $template = VaccinationTemplate::with(['items.vaccination', 'program'])
            ->where(fn($query) => $query->where('doctor_id', $doctor->id)->orWhereNull('doctor_id'))
            ->where('is_active', true)
            ->findOrFail($data['template_id']);

        $firstDoseDateInput = $data['first_dose_date'] ?? $data['start_date'] ?? now()->toDateString();
        $firstDoseDate = Carbon::parse($firstDoseDateInput)->startOfDay();

        $alreadyAssigned = PatientVaccination::query()
            ->where('patient_id', $patient->id)
            ->where('doctor_id', $doctor->id)
            ->where('vaccination_template_id', $template->id)
            ->exists();

        if ($alreadyAssigned) {
            return ApiResponseService::validationError('This vaccination template is already assigned to this patient.');
        }

        $createdCount = DB::transaction(function () use ($template, $patient, $doctor, $firstDoseDate) {
            return $this->createTemplateDosesForPatient(
                patient: $patient,
                doctorId: $doctor->id,
                template: $template,
                startDate: $firstDoseDate
            );
        });

        return ApiResponseService::created(data: [
            'template_id' => $template->id,
            'template_name' => $template->name,
            'patient_id' => $patient->id,
            'assigned_date' => $firstDoseDate->toDateString(),
            'created_doses' => $createdCount,
        ]);
    }

    public function assignCustomVaccination(Request $request, string $patientId)
    {
        $doctor = $this->doctor($request);
        if (! $doctor) {
            return ApiResponseService::unauthorized();
        }

        Patient::findOrFail($patientId);

        $data = $request->validate([
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
        Patient::findOrFail($patientId);
        $status = $data['status'] ?? (! empty($data['scheduled_date']) ? VaccinationStatus::SCHEDULED : VaccinationStatus::PENDING);

        $patientVaccination = PatientVaccination::create([
            'patient_id' => $patientId,
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
            data: new PatientVaccinationResource($patientVaccination->load(['vaccination', 'documents', 'template.program', 'patient.user']))
        );
    }

    private function createTemplateDosesForPatient(Patient $patient, string $doctorId, VaccinationTemplate $template, Carbon $startDate): int
    {
        $template->loadMissing(['items.vaccination', 'program']);

        $ageBaseDate = $startDate->copy();

        $previousDate = $startDate->copy();
        $created = 0;

        foreach ($template->items as $index => $item) {
            $timingType = $item->effectiveTimingType();
            $scheduledDate = null;

            if ($timingType !== 'doctor_manual_date') {
                $baseDate = $timingType === 'previous_dose' ? $previousDate : $ageBaseDate;
                $value = $timingType === 'previous_dose'
                    ? $item->effectiveIntervalValue()
                    : $item->effectiveOffsetValue();
                $unit = $timingType === 'previous_dose'
                    ? $item->effectiveIntervalUnit()
                    : $item->effectiveOffsetUnit();

                $scheduledDate = $this->addValueUnitToDate($baseDate, $value, $unit);
                $previousDate = $scheduledDate->copy()->startOfDay();
            }

            $dose = PatientVaccination::create([
                'patient_id' => $patient->id,
                'doctor_id' => $doctorId,
                'vaccination_id' => $item->vaccination_id,
                'vaccination_template_id' => $template->id,
                'set_name' => $item->set_name,
                'set_description' => $item->set_description,
                'set_sort_order' => $item->set_sort_order ?? ($index + 1),
                'recommended_age_label' => $item->recommended_age_label,
                'dose_no' => $item->dose_no ?? ($index + 1),
                'first_dose_date' => $startDate->toDateString(),
                'due_after_days' => $item->due_after_days ?? 0,
                'due_after_months' => $item->due_after_months ?? 0,
                'scheduled_date' => $scheduledDate?->toDateString(),
                'expected_date' => $scheduledDate?->toDateString(),
                'assigned_date' => $startDate->toDateString(),
                'due_date' => $scheduledDate?->toDateString(),
                'grace_period_before_days' => $item->grace_period_before_days ?? 0,
                'grace_period_after_days' => $item->grace_period_after_days ?? 0,
                'status' => VaccinationStatus::UPCOMING->value,
                'manufacturer' => $item->vaccination?->manufacturer,
                'reminder_sent' => false,
                'next_reminder_at' => ($scheduledDate && $scheduledDate->copy()->subDay()->greaterThanOrEqualTo(Carbon::create(1970, 1, 2)))
                    ? $scheduledDate->copy()->subDay()
                    : null,
            ]);

            if ($dose) {
                $created++;
            }
        }

        return $created;
    }

    private function addValueUnitToDate(Carbon $date, int $value, string $unit): Carbon
    {
        return match ($unit) {
            'weeks' => $date->copy()->addWeeks($value),
            'months' => $date->copy()->addMonths($value),
            'years' => $date->copy()->addYears($value),
            default => $date->copy()->addDays($value),
        };
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
            'expected_date' => ['nullable', 'date'],
            'assigned_date' => ['nullable', 'date'],
            'due_date' => ['nullable', 'date'],
            'changed_date' => ['nullable', 'date'],
            'grace_period_before_days' => ['nullable', 'integer', 'min:0'],
            'grace_period_after_days' => ['nullable', 'integer', 'min:0'],
            'skipped_reason' => ['nullable', 'string'],
            'on_hold_reason' => ['nullable', 'string'],
        ]);

        if (($data['status'] ?? null) === VaccinationStatus::SKIPPED_BY_DOCTOR->value && empty($data['skipped_reason'])) {
            return ApiResponseService::validationError('Skipped reason is required when skipping a dose.');
        }

        if (($data['status'] ?? null) === VaccinationStatus::ON_HOLD->value && empty($data['on_hold_reason'])) {
            return ApiResponseService::validationError('On hold reason is required when placing a dose on hold.');
        }

        if (isset($data['due_date']) && $patientVaccination->due_date && Carbon::parse($data['due_date'])->toDateString() !== $patientVaccination->due_date->toDateString()) {
            if (!isset($data['status'])) {
                $data['status'] = VaccinationStatus::RESCHEDULED->value;
            }
            $data['changed_date'] = now()->toDateString();
        }

        if (($data['status'] ?? null) === VaccinationStatus::COMPLETED->value && empty($data['completed_date'])) {
            $data['completed_date'] = now()->toDateString();
        }

        $patientVaccination->update($data);

        return ApiResponseService::success(
            data: new PatientVaccinationResource($patientVaccination->refresh()->load(['vaccination', 'documents', 'template.program', 'patient.user', 'logs.performedBy']))
        );
    }

    public function completeMultiple(Request $request)
    {
        $doctor = $this->doctor($request);
        if (! $doctor) {
            return ApiResponseService::unauthorized();
        }

        $data = $request->validate([
            'ids' => ['required', 'array'],
            'ids.*' => ['required', 'string', 'exists:patient_vaccinations,id'],
            'completed_date' => ['nullable', 'date'],
            'batch_number' => ['nullable', 'string', 'max:255'],
            'manufacturer' => ['nullable', 'string', 'max:255'],
            'route' => ['nullable', 'string', 'max:255'],
            'site' => ['nullable', 'string', 'max:255'],
            'dose_amount' => ['nullable', 'string', 'max:255'],
            'given_at' => ['nullable', 'string', 'max:255'],
            'given_by' => ['nullable', 'string', 'max:255'],
            'doctor_notes' => ['nullable', 'string'],
        ]);

        $vaccinations = PatientVaccination::whereIn('id', $data['ids'])
            ->get();

        if ($vaccinations->isEmpty()) {
            return ApiResponseService::validationError('No matching vaccinations found for this doctor.');
        }

        $updateData = collect($data)->except(['ids'])->all();
        $completedDate = $data['completed_date'] ?? now()->toDateString();

        DB::transaction(function () use ($vaccinations, $updateData, $completedDate) {
            foreach ($vaccinations as $vaccination) {
                $vaccination->update(array_merge($updateData, [
                    'status' => VaccinationStatus::COMPLETED->value,
                    'completed_date' => $completedDate,
                ]));
            }
        });

        return ApiResponseService::success(data: ['message' => 'Doses completed successfully.']);
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
            'vaccination_image' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'certificate_number' => ['nullable', 'string', 'max:255'],
        ]);

        $updateData = collect($data)->except(['vaccination_image', 'certificate_number'])->all();

        $patientVaccination->update(array_merge($updateData, [
            'status' => VaccinationStatus::COMPLETED,
            'completed_date' => $data['completed_date'] ?? now()->toDateString(),
        ]));

        if ($request->hasFile('vaccination_image')) {
            $file = $request->file('vaccination_image');
            $filename = Str::uuid() . '.' . strtolower($file->getClientOriginalExtension());
            $path = $file->storeAs('vaccination_documents', $filename, 'public');

            $patientVaccination->documents()->create([
                'document' => $path,
                'document_type' => VaccinationDocumentType::CERTIFICATE->value,
                'certificate_number' => $data['certificate_number'] ?? null,
            ]);
        }

        return ApiResponseService::success(
            data: new PatientVaccinationResource($patientVaccination->refresh()->load(['vaccination', 'documents', 'template.program', 'patient.user', 'logs.performedBy']))
        );
    }

    public function addDocument(Request $request, string $id)
    {
        $patientVaccination = $this->ownedPatientVaccination($request, $id);
        if (! $patientVaccination) {
            return ApiResponseService::unauthorized();
        }

        $data = $request->validate([
            'document' => ['nullable', 'required_without:file', 'string', 'max:255'],
            'file' => ['nullable', 'required_without:document', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:5120'],
            'document_type' => ['sometimes', Rule::in(VaccinationDocumentType::values())],
            'certificate_number' => ['nullable', 'string', 'max:255'],
        ]);

        $documentPath = $data['document'] ?? null;
        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $filename = Str::uuid() . '.' . strtolower($file->getClientOriginalExtension());
            $documentPath = $file->storeAs('vaccination_documents', $filename, 'public');
        }

        $patientVaccination->documents()->create([
            'document' => $documentPath,
            'document_type' => $data['document_type'] ?? VaccinationDocumentType::CERTIFICATE->value,
            'certificate_number' => $data['certificate_number'] ?? null,
        ]);

        return ApiResponseService::created(
            data: new PatientVaccinationResource($patientVaccination->refresh()->load(['vaccination', 'documents', 'template.program', 'patient.user']))
        );
    }

    public function deleteDocument(Request $request, string $documentId)
    {
        $doctor = $this->doctor($request);
        if (! $doctor) {
            return ApiResponseService::unauthorized();
        }

        $document = VaccinationDocument::findOrFail($documentId);

        $document->delete();

        return ApiResponseService::success();
    }

    public function patientVaccinations(Request $request, PatientVaccinationOverviewService $overviewService)
    {
        $patient = $request->user()?->patient;
        if (! $patient) {
            return ApiResponseService::unauthorized();
        }

        $validated = $request->validate([
            'filter' => ['nullable', Rule::in(['all', 'completed', 'upcoming'])],
        ]);

        $filter = strtolower((string) ($validated['filter'] ?? 'all'));

        return ApiResponseService::success(
            data: $overviewService->build(
                $patient,
                $request->integer('page', 1),
                $request->integer('per_page', 10),
                $filter
            )
        );
    }

    public function patientShow(Request $request, string $id)
    {
        $patient = $request->user()?->patient;
        if (! $patient) {
            return ApiResponseService::unauthorized();
        }

        $vaccination = PatientVaccination::with(['vaccination.faqs', 'documents', 'template.program', 'patient.user'])
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

        $vaccinations = PatientVaccination::with(['vaccination', 'documents', 'template.program', 'patient.user'])
            ->where('patient_id', $patient->id)
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

        return PatientVaccination::with(['vaccination', 'documents', 'template.program', 'patient.user', 'logs.performedBy'])
            ->where('id', $id)
            ->first();
    }

    private function doctor(Request $request)
    {
        return $request->user()?->doctor;
    }
}
