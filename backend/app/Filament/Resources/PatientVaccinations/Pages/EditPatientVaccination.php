<?php

namespace App\Filament\Resources\PatientVaccinations\Pages;

use App\Filament\Resources\PatientVaccinations\PatientVaccinationResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Enums\Alignment;

class EditPatientVaccination extends EditRecord
{
    protected static string $resource = PatientVaccinationResource::class;

    protected string $view = 'filament.resources.patient-vaccinations.pages.edit-patient-vaccination';
    public function getFormActionsAlignment(): Alignment|string
    {
        return Alignment::End;
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
