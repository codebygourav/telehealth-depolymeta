<?php

namespace App\Filament\Resources\PatientVaccinations\Pages;

use App\Filament\Resources\PatientVaccinations\PatientVaccinationResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewPatientVaccination extends ViewRecord
{
    protected static string $resource = PatientVaccinationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
