<?php

namespace App\Filament\Resources\PatientVaccinationPrograms\Pages;

use App\Filament\Resources\PatientVaccinationPrograms\PatientVaccinationProgramResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewPatientVaccinationProgram extends ViewRecord
{
    protected static string $resource = PatientVaccinationProgramResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
