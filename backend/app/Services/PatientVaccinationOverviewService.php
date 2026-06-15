<?php

namespace App\Services;

use App\Enums\PatientProfileType;
use App\Enums\VaccinationStatus;
use App\Http\Resources\Vaccination\VaccinationClinicalInsightResource;
use App\Http\Resources\Vaccination\VaccinationFaqResource;
use App\Http\Resources\Vaccination\VaccinationResource;
use App\Models\Patient;
use App\Repositories\VaccinationModuleContentRepository;
use App\Models\PatientProfile;
use App\Models\PatientVaccination;
use App\Models\Vaccination;
use App\Models\VaccinationTemplateItem;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class PatientVaccinationOverviewService
{
    public function __construct(
        protected VaccinationModuleContentRepository $contentRepository
    ) {}

    public function build(Patient $patient, ?string $patientProfileId = null): array
    {
        $profile = $this->resolveProfile($patient, $patientProfileId);

        $vaccinations = PatientVaccination::query()
            ->with(['vaccination.faqs', 'documents', 'template.program', 'patient.user', 'patientProfile'])
            ->where('patient_id', $patient->id)
            ->when($profile, fn($query) => $query->where('patient_profile_id', $profile->id))
            ->orderBy('set_sort_order')
            ->orderByRaw('scheduled_date IS NULL, scheduled_date ASC')
            ->get();

        $setDescriptions = $this->setDescriptionsForVaccinations($vaccinations);
        $schedule = $this->buildSchedule($vaccinations, $setDescriptions);
        $summary = $this->buildSummary($vaccinations);
        $supplementary = $this->contentRepository->getOverviewSupplementaryContent();

        return [
            'profile' => $this->buildProfile($patient, $profile),
            'vaccination_summary' => $summary,
            'vaccination_schedule' => $schedule,
            'faqs' => VaccinationFaqResource::collection($supplementary['faqs'])->resolve(),
            'clinical_insight' => (new VaccinationClinicalInsightResource($supplementary['clinical_insight']))->resolve(),
        ];
    }

    private function resolveProfile(Patient $patient, ?string $patientProfileId): ?PatientProfile
    {
        if ($patientProfileId) {
            return PatientProfile::where('patient_id', $patient->id)->findOrFail($patientProfileId);
        }

        return $patient->profiles()->where('is_primary', true)->first()
            ?? $patient->profiles()->where('profile_type', PatientProfileType::SELF->value)->first();
    }

    private function buildProfile(Patient $patient, ?PatientProfile $profile): array
    {
        $usePatientRecord = ! $profile || $this->isSelfProfile($profile);

        if ($usePatientRecord) {
            return [
                'id' => $patient->id,
                'patient_user_id' => $patient->user_id,
                'name' => trim("{$patient->first_name} {$patient->last_name}") ?: null,
                'profile_type' => PatientProfileType::SELF->value,
                'age' => $this->formatAge($patient->date_of_birth),
                'weight' => $this->formatWeight($patient->weight),
                'height' => $this->formatHeight($patient->height),
                'blood_group' => $patient->blood_group,
                'gender' => $this->formatGender($patient->gender),
                'photo' => $patient->avatar,
            ];
        }

        return [
            'id' => $profile->id,
            'patient_user_id' => $patient->user_id,
            'name' => $profile->name,
            'profile_type' => $profile->profile_type instanceof PatientProfileType
                ? $profile->profile_type->value
                : $profile->profile_type,
            'age' => $this->formatAge($profile->date_of_birth),
            'weight' => $this->formatWeight($profile->weight),
            'height' => $this->formatHeight($profile->height),
            'blood_group' => $profile->blood_group,
            'gender' => $this->formatGender($profile->gender),
            'photo' => $patient->avatar,
        ];
    }

    private function isSelfProfile(PatientProfile $profile): bool
    {
        $type = $profile->profile_type instanceof PatientProfileType
            ? $profile->profile_type
            : PatientProfileType::tryFrom((string) $profile->profile_type);

        return $type === PatientProfileType::SELF;
    }

    private function buildSummary(Collection $vaccinations): array
    {
        $total = $vaccinations->count();
        $completed = $vaccinations->filter(fn(PatientVaccination $row) => $this->statusValue($row) === VaccinationStatus::COMPLETED->value)->count();

        $nextDue = $vaccinations
            ->filter(fn(PatientVaccination $row) => in_array($this->statusValue($row), [
                VaccinationStatus::PENDING->value,
                VaccinationStatus::SCHEDULED->value,
            ], true) && $row->scheduled_date)
            ->sortBy('scheduled_date')
            ->first();

        return [
            'completed_percentage' => $total > 0 ? (int) round(($completed / $total) * 100) : 0,
            'completed_count' => $completed,
            'total_count' => $total,
            'next_due_date' => $nextDue?->scheduled_date?->format('j M Y'),
        ];
    }

    private function buildSchedule(Collection $vaccinations, array $setDescriptions): array
    {
        $groups = $vaccinations
            ->groupBy(fn(PatientVaccination $row) => trim((string) ($row->set_name ?: 'General')))
            ->sortBy(fn(Collection $items) => $items->min(fn(PatientVaccination $row) => $row->set_sort_order ?? 0));

        $sets = [];
        $expandedAssigned = false;

        foreach ($groups as $setName => $items) {
            $first = $items->first();
            $sortOrder = $items->min(fn(PatientVaccination $row) => $row->set_sort_order ?? 0);
            $setStatus = $this->resolveSetStatus($items);
            $expanded = ! $expandedAssigned && $setStatus === 'upcoming';
            if ($expanded) {
                $expandedAssigned = true;
            }

            $sets[] = [
                'set_id' => $sortOrder ?: count($sets) + 1,
                'set_name' => $setName,
                'description' => $first?->set_description
                    ?? $setDescriptions[$setName]
                    ?? $this->defaultSetDescription($setStatus),
                'status' => $setStatus,
                'expanded' => $expanded,
                'vaccinations' => $items->map(fn(PatientVaccination $row) => $this->mapScheduleVaccination($row))->values()->all(),
            ];
        }

        if (! $expandedAssigned && count($sets) > 0) {
            foreach ($sets as $index => $set) {
                if ($set['status'] !== 'completed') {
                    $sets[$index]['expanded'] = true;
                    break;
                }
            }
        }

        return $sets;
    }

    private function mapScheduleVaccination(PatientVaccination $row): array
    {
        $vaccination = $row->vaccination;

        return [
            'id' => $row->id,
            'vaccine_name' => $vaccination?->name,
            'short_description' => $vaccination?->disease_for ?: $vaccination?->short_name,
            'recommended_age' => $row->recommended_age_label,
            'due_date' => $row->scheduled_date?->format('j M Y'),
            'dose_no' => $row->dose_no,
            'administration_route' => $row->route,
            'body_site' => $row->site,
            'dose_amount' => $row->dose_amount,
            'given_at' => $row->given_at,
            'given_by' => $row->given_by,
            'completed_date' => $row->completed_date?->format('j M Y'),
            'patient_vaccination_program_id' => $row->patient_vaccination_program_id,
            'status' => $this->statusValue($row),
            'status_label' => $row->status instanceof VaccinationStatus ? $row->status->label() : ucfirst((string) $row->status),
            'batch_number' => $row->batch_number,
            'manufacturer' => $row->manufacturer,
            'doctor_notes' => $row->doctor_notes,
            'side_effect_observed' => $row->side_effect_observed,
            'patient_reaction' => $row->patient_reaction,
            'information' => array_merge(
                $vaccination ? (new VaccinationResource($vaccination))->resolve() : [],
                [
                    'faqs' => $this->vaccinationFaqs($vaccination),
                ]
            ),
            'documents' => $row->relationLoaded('documents')
                ? $row->documents->map(function($document) {
                    return [
                        'id' => $document->id,
                        'document_url' => $document->document ? asset('storage/' . $document->document) : null,
                        'document_type' => $document->document_type instanceof \App\Enums\VaccinationDocumentType
                            ? $document->document_type->value
                            : $document->document_type,
                        'certificate_number' => $document->certificate_number,
                    ];
                })->all()
                : [],

        ];
    }

    private function vaccinationFaqs(?Vaccination $vaccination): array
    {
        if (! $vaccination || ! $vaccination->relationLoaded('faqs')) {
            return [];
        }

        return VaccinationFaqResource::collection($vaccination->faqs)->resolve();
    }

    private function resolveSetStatus(Collection $items): string
    {
        if ($items->isEmpty()) {
            return 'future';
        }

        $statuses = $items->map(fn(PatientVaccination $row) => $this->statusValue($row));

        if ($statuses->every(fn(string $status) => $status === VaccinationStatus::COMPLETED->value)) {
            return 'completed';
        }

        $hasUpcoming = $items->contains(function (PatientVaccination $row) {
            $status = $this->statusValue($row);

            if ($status === VaccinationStatus::COMPLETED->value) {
                return false;
            }

            if (! $row->scheduled_date) {
                return $status === VaccinationStatus::PENDING->value;
            }

            return $row->scheduled_date->lte(now()->addMonths(3));
        });

        if ($hasUpcoming) {
            return 'upcoming';
        }

        return 'future';
    }

    private function defaultSetDescription(string $setStatus): string
    {
        return match ($setStatus) {
            'completed' => 'All immunizations in this set are completed.',
            'upcoming' => 'Upcoming immunizations for the current growth phase.',
            default => 'Long-term vaccination roadmap.',
        };
    }

    private function mapDoseDisplayStatus(PatientVaccination $row): string
    {
        $status = $this->statusValue($row);

        if ($status === VaccinationStatus::COMPLETED->value) {
            return 'completed';
        }

        if ($status === VaccinationStatus::SCHEDULED->value && $row->scheduled_date?->isFuture()) {
            return 'upcoming';
        }

        return 'pending';
    }

    private function statusValue(PatientVaccination $row): string
    {
        return $row->status instanceof VaccinationStatus ? $row->status->value : (string) $row->status;
    }

    private function setDescriptionsForVaccinations(Collection $vaccinations): array
    {
        $templateIds = $vaccinations->pluck('vaccination_template_id')->filter()->unique()->values();

        if ($templateIds->isEmpty()) {
            return [];
        }

        return VaccinationTemplateItem::query()
            ->whereIn('vaccination_template_id', $templateIds)
            ->whereNotNull('set_name')
            ->get()
            ->unique(fn(VaccinationTemplateItem $item) => $item->set_name)
            ->mapWithKeys(fn(VaccinationTemplateItem $item) => [$item->set_name => $item->set_description])
            ->all();
    }

    private function formatAge(mixed $dateOfBirth): ?string
    {
        if (! $dateOfBirth) {
            return null;
        }

        $dob = $dateOfBirth instanceof Carbon ? $dateOfBirth : Carbon::parse($dateOfBirth);
        $years = (int) $dob->diffInYears(now());
        $months = (int) $dob->diffInMonths(now()) % 12;

        if ($years >= 2) {
            return $years === 1 ? '1 year old' : "{$years} years old";
        }

        $totalMonths = (int) $dob->diffInMonths(now());
        if ($totalMonths <= 0) {
            $weeks = max(1, (int) $dob->diffInWeeks(now()));

            return $weeks === 1 ? '1 week old' : "{$weeks} weeks old";
        }

        return $totalMonths === 1 ? '1 month old' : "{$totalMonths} months old";
    }

    private function formatWeight(mixed $weight): ?string
    {
        if ($weight === null || $weight === '') {
            return null;
        }

        return rtrim(rtrim((string) $weight, '0'), '.') . ' kg';
    }

    private function formatHeight(mixed $height): ?string
    {
        if ($height === null || $height === '') {
            return null;
        }

        return rtrim(rtrim((string) $height, '0'), '.') . ' cm';
    }

    private function formatGender(?string $gender): ?string
    {
        if (! $gender) {
            return null;
        }

        return ucfirst($gender);
    }
}
