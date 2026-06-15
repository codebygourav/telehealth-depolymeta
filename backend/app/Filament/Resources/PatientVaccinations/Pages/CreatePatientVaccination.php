<?php

namespace App\Filament\Resources\PatientVaccinations\Pages;

use App\Filament\Resources\PatientVaccinations\PatientVaccinationResource;
use App\Models\Patient;
use App\Models\PatientVaccination;
use Filament\Resources\Pages\CreateRecord;
use Filament\Support\Enums\Alignment;
use Illuminate\Http\Request;

class CreatePatientVaccination extends CreateRecord
{
    protected static string $resource = PatientVaccinationResource::class;

    public function getFormActionsAlignment(): Alignment|string
    {
        return Alignment::End;
    }

    public function mount(): void
    {
        parent::mount();

        $vaccinationId = request()->query('vaccination_id');
        $patientId = request()->query('patient_id');

        if ($patientId) {
            $this->form->fill([
                'patient_id' => $patientId,
                'vaccination_id' => $vaccinationId,
            ]);
        } elseif ($vaccinationId) {
            $this->form->fill([
                'vaccination_id' => $vaccinationId,
            ]);
        } else {
            $this->form->fill();
        }
    }

    public function getHeading(): string
    {
        $patientId = request()->query('patient_id');
        if ($patientId) {
            try {
                $patient = Patient::findOrFail($patientId);
                $name = trim(($patient->first_name ?? '') . ' ' . ($patient->last_name ?? '')) ?: 'Patient';
                return "Assign Vaccination to $name";
            } catch (\Throwable $e) {
                return parent::getHeading();
            }
        }

        return parent::getHeading();
    }

    protected function getRedirectUrl(): string
    {
        $patientId = request()->query('patient_id');

        if ($patientId) {
            return route('filament.admin.resources.patient-vaccinations.index', ['tableFilters' => ['patient_id' => $patientId]]);
        }

        return $this->getResource()::getUrl('index');
    }
}
