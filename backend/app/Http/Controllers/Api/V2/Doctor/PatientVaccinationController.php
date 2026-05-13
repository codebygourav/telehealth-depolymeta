<?php

namespace App\Http\Controllers\Api\V2\Doctor;

use App\Enums\VaccinationStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\Vaccination\PatientVaccinationResource;
use App\Models\Patient;
use App\Models\PatientVaccination;
use App\Models\Vaccination;
use App\Models\VaccinationTemplate;
use App\Services\ApiResponseService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class PatientVaccinationController extends Controller
{
    public function index(Request $request, string $patientId)
    {
        $doctor = $this->doctor($request);
        if (! $doctor) {
            return ApiResponseService::unauthorized();
        }

        Patient::findOrFail($patientId);

        $validated = $request->validate([
            'status' => ['nullable', Rule::in(VaccinationStatus::values())],
        ]);

        $vaccinations = PatientVaccination::with(['vaccination', 'documents', 'template', 'patient.user'])
            ->where('patient_id', $patientId)
            ->where('doctor_id', $doctor->id)
            ->when(! empty($validated['status']), fn ($query) => $query->where('status', $validated['status']))
            ->orderBy('set_sort_order')
            ->orderByRaw('scheduled_date IS NULL, scheduled_date ASC')
            ->latest()
            ->paginate($request->integer('per_page', 10));

        return ApiResponseService::paginated($vaccinations->through(fn ($vaccination) => new PatientVaccinationResource($vaccination)));
    }

    public function assignTemplate(Request $request, string $patientId)
    {
        $doctor = $this->doctor($request);
        if (! $doctor) {
            return ApiResponseService::unauthorized();
        }

        Patient::findOrFail($patientId);

        $data = $request->validate([
            'template_id' => ['required', 'exists:vaccination_templates,id'],
            'first_dose_date' => ['nullable', 'date'],
            'start_date' => ['nullable', 'date'],
        ]);

        $template = VaccinationTemplate::with(['items.vaccination'])
            ->where('doctor_id', $doctor->id)
            ->where('is_active', true)
            ->findOrFail($data['template_id']);

        $firstDoseDateInput = $data['first_dose_date'] ?? $data['start_date'] ?? now()->toDateString();
        $firstDoseDate = Carbon::parse($firstDoseDateInput)->startOfDay();

        $created = DB::transaction(function () use ($template, $patientId, $doctor, $firstDoseDate) {
            $cursorDate = $firstDoseDate->copy();

            return $template->items->map(function ($item, $index) use ($template, $patientId, $doctor, $firstDoseDate, &$cursorDate) {
                $scheduledDate = $this->scheduleDateForItem($cursorDate, $item);
                $cursorDate = Carbon::parse($scheduledDate)->startOfDay();

                return PatientVaccination::updateOrCreate([
                    'patient_id' => $patientId,
                    'doctor_id' => $doctor->id,
                    'vaccination_id' => $item->vaccination_id,
                    'vaccination_template_id' => $template->id,
                    'dose_no' => $item->dose_no ?? ($index + 1),
                    'set_name' => $item->set_name,
                ], [
                    'set_name' => $item->set_name,
                    'set_sort_order' => $item->set_sort_order ?? 0,
                    'recommended_age_label' => $item->recommended_age_label,
                    'dose_no' => $item->dose_no ?? ($index + 1),
                    'first_dose_date' => $firstDoseDate->toDateString(),
                    'due_after_days' => $item->due_after_days ?? 0,
                    'due_after_months' => $item->due_after_months ?? 0,
                    'scheduled_date' => $scheduledDate,
                    'status' => VaccinationStatus::SCHEDULED,
                    'manufacturer' => $item->vaccination?->manufacturer,
                    'reminder_sent' => false,
                ]);
            });
        });

        return ApiResponseService::created(
            data: PatientVaccinationResource::collection($created->load(['vaccination', 'documents', 'template', 'patient.user']))
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
        ]);

        return ApiResponseService::created(
            data: new PatientVaccinationResource($patientVaccination->load(['vaccination', 'documents', 'template', 'patient.user']))
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
            data: new PatientVaccinationResource($patientVaccination->refresh()->load(['vaccination', 'documents', 'template', 'patient.user']))
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
            data: new PatientVaccinationResource($patientVaccination->refresh()->load(['vaccination', 'documents', 'template', 'patient.user']))
        );
    }

    public function patientVaccinations(Request $request)
    {
        return $this->patientList($request);
    }

    public function patientShow(Request $request, string $id)
    {
        $patient = $request->user()?->patient;
        if (! $patient) {
            return ApiResponseService::unauthorized();
        }

        $vaccination = PatientVaccination::with(['vaccination', 'documents', 'template', 'patient.user'])
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

        $vaccinations = PatientVaccination::with(['vaccination', 'documents', 'template', 'patient.user'])
            ->where('patient_id', $patient->id)
            ->when($statuses, fn ($query) => $query->whereIn('status', $statuses))
            ->when($futureOnly, fn ($query) => $query->whereDate('scheduled_date', '>=', now()->toDateString()))
            ->orderBy('set_sort_order')
            ->orderByRaw('scheduled_date IS NULL, scheduled_date ASC')
            ->latest()
            ->paginate($request->integer('per_page', 10));

        return ApiResponseService::paginated($vaccinations->through(fn ($vaccination) => new PatientVaccinationResource($vaccination)));
    }

    private function ownedPatientVaccination(Request $request, string $id): ?PatientVaccination
    {
        $doctor = $this->doctor($request);
        if (! $doctor) {
            return null;
        }

        return PatientVaccination::where('doctor_id', $doctor->id)
            ->where('id', $id)
            ->first();
    }

    private function doctor(Request $request)
    {
        return $request->user()?->doctor;
    }

    private function scheduleDateForItem(Carbon $firstDoseDate, mixed $item): string
    {
        $months = (int) ($item->due_after_months ?? 0);
        $days = (int) ($item->due_after_days ?? 0);

        return $firstDoseDate
            ->copy()
            ->addMonths($months)
            ->addDays($days)
            ->toDateString();
    }
}
