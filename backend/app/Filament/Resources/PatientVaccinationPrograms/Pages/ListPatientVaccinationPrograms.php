<?php

namespace App\Filament\Resources\PatientVaccinationPrograms\Pages;

use App\Filament\Resources\PatientVaccinationPrograms\PatientVaccinationProgramResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListPatientVaccinationPrograms extends ListRecords
{
    protected static string $resource = PatientVaccinationProgramResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
