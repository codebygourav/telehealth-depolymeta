<?php

namespace App\Http\Resources\Vaccination;

use App\Enums\VaccinationStatus;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PatientVaccinationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $status = $this->status instanceof VaccinationStatus ? $this->status->value : $this->status;
        $effectiveStatus = $this->effectiveStatus($status);
        $patientDob = $this->patient?->date_of_birth;

        return [
            'id' => $this->id,
            'patient_id' => $this->patient_id,
            'patient_vaccination_program_id' => $this->patient_vaccination_program_id,
            'patient' => $this->whenLoaded('patient', fn() => [
                'id' => $this->patient?->id,
                'name' => trim((string) ($this->patient?->first_name . ' ' . $this->patient?->last_name)),
                'email' => $this->patient?->email ?? $this->patient?->user?->email,
                'phone' => $this->patient?->mobile_no ?? $this->patient?->user?->phone,
            ]),
            'doctor_id' => $this->doctor_id,
            'vaccination_id' => $this->vaccination_id,
            'vaccination_template_id' => $this->vaccination_template_id,
            'set_name' => $this->set_name,
            'set_sort_order' => $this->set_sort_order,
            'recommended_age_label' => $this->recommended_age_label,
            'status' => $status,
            'status_label' => $this->status instanceof VaccinationStatus ? $this->status->label() : ucfirst((string) $this->status),
            'effective_status' => $effectiveStatus,
            'effective_status_label' => ucfirst($effectiveStatus),
            'is_overdue' => $effectiveStatus === 'overdue',
            'dose_no' => $this->dose_no,
            'first_dose_date' => optional($this->first_dose_date)?->format('Y-m-d'),
            'due_after_days' => $this->due_after_days,
            'due_after_months' => $this->due_after_months,
            'expected_date' => optional($this->expected_date)?->format('Y-m-d'),
            'assigned_date' => optional($this->assigned_date)?->format('Y-m-d'),
            'due_date' => optional($this->due_date)?->format('Y-m-d'),
            'changed_date' => optional($this->changed_date)?->format('Y-m-d'),
            'overdue_date' => optional($this->overdue_date)?->format('Y-m-d'),
            'missed_date' => optional($this->missed_date)?->format('Y-m-d'),
            'grace_period_before_days' => $this->grace_period_before_days,
            'grace_period_after_days' => $this->grace_period_after_days,
            'skipped_reason' => $this->skipped_reason,
            'on_hold_reason' => $this->on_hold_reason,
            'patient_age' => $this->formatAge($patientDob),
            'patient_age_on_schedule' => $this->formatAge($patientDob, $this->due_date ?: $this->scheduled_date),
            'scheduled_date' => optional($this->scheduled_date)?->format('Y-m-d'),
            'completed_date' => optional($this->completed_date)?->format('Y-m-d'),
            'batch_number' => $this->batch_number,
            'manufacturer' => $this->manufacturer,
            'route' => $this->route,
            'site' => $this->site,
            'dose_amount' => $this->dose_amount,
            'given_at' => $this->given_at,
            'given_by' => $this->given_by,
            'doctor_notes' => $this->doctor_notes,
            'side_effect_observed' => $this->side_effect_observed,
            'patient_reaction' => $this->patient_reaction,
            'reminder_sent' => $this->reminder_sent,
            'last_reminder_sent_at' => optional($this->last_reminder_sent_at)?->toIso8601String(),
            'reminder_count' => $this->reminder_count,
            'next_reminder_at' => optional($this->next_reminder_at)?->toIso8601String(),
            'vaccination' => [
                'id' => $this->vaccination?->id,
                'name' => $this->vaccination?->name,
                'short_name' => $this->vaccination?->short_name,
                'manufacturer' => $this->vaccination?->manufacturer,
                'disease_for' => $this->vaccination?->disease_for,
                'description' => $this->vaccination?->description,
                'side_effects' => $this->vaccination?->side_effects,
                'contraindications' => $this->vaccination?->contraindications,
                'prevention' => $this->vaccination?->contraindications,
                'precautions' => $this->vaccination?->precautions,
                'dosage_information' => $this->vaccination?->dosage_information,
                'is_multi_dose' => $this->vaccination?->is_multi_dose,
                'total_doses' => $this->vaccination?->total_doses,
                'minimum_age_days' => $this->vaccination?->minimum_age_days,
                'maximum_age_days' => $this->vaccination?->maximum_age_days,
                'gender_restriction' => $this->vaccination?->gender_restriction instanceof \App\Enums\VaccinationGenderRestriction
                    ? $this->vaccination?->gender_restriction->value
                    : $this->vaccination?->gender_restriction,
            ],
            'template' => $this->whenLoaded('template', fn() => [
                'id' => $this->template?->id,
                'name' => $this->template?->name,
                'vaccination_program_id' => $this->template?->vaccination_program_id,
            ]),
            'documents' => $this->whenLoaded('documents', fn() => $this->documents->map(fn($document) => [
                'id' => $document->id,
                'document' => $document->document,
                'document_url' => $document->document ? asset('storage/' . $document->document) : null,
                'document_type' => $document->document_type instanceof \App\Enums\VaccinationDocumentType ? $document->document_type->value : $document->document_type,
                'certificate_number' => $document->certificate_number,
            ])),
            'logs' => $this->whenLoaded('logs', fn() => $this->logs->map(fn($log) => [
                'id' => $log->id,
                'action' => $log->action,
                'old_value' => $log->old_value,
                'new_value' => $log->new_value,
                'reason' => $log->reason,
                'performed_by' => $log->performedBy ? [
                    'id' => $log->performedBy->id,
                    'name' => $log->performedBy->name,
                ] : null,
                'created_at' => $log->created_at?->toIso8601String(),
            ])),
            'created_at' => optional($this->created_at)?->toIso8601String(),
            'updated_at' => optional($this->updated_at)?->toIso8601String(),
        ];
    }

    private function effectiveStatus(?string $status): string
    {
        if ($status === VaccinationStatus::COMPLETED->value) {
            return VaccinationStatus::COMPLETED->value;
        }

        if (
            in_array($status, [VaccinationStatus::PENDING->value, VaccinationStatus::SCHEDULED->value], true)
            && $this->scheduled_date
        ) {
            if ($this->scheduled_date->isToday()) {
                return 'due';
            }

            if ($this->scheduled_date->isPast()) {
                return 'overdue';
            }
        }

        return $status ?: VaccinationStatus::PENDING->value;
    }

    private function formatAge(mixed $dateOfBirth, mixed $asOf = null): ?string
    {
        if (! $dateOfBirth) {
            return null;
        }

        $dob = $dateOfBirth instanceof Carbon ? $dateOfBirth : Carbon::parse($dateOfBirth);
        $targetDate = $asOf ? ($asOf instanceof Carbon ? $asOf : Carbon::parse($asOf)) : now();
        if ($targetDate->lt($dob)) {
            return null;
        }

        $years = (int) $dob->diffInYears($targetDate);
        $months = (int) $dob->diffInMonths($targetDate) % 12;

        if ($years > 0) {
            return $months > 0 ? "{$years}y {$months}m" : "{$years}y";
        }

        $totalMonths = (int) $dob->diffInMonths($targetDate);
        if ($totalMonths > 0) {
            return "{$totalMonths}m";
        }

        $weeks = (int) $dob->diffInWeeks($targetDate);
        return $weeks > 0 ? "{$weeks}w" : 'Birth';
    }
}
