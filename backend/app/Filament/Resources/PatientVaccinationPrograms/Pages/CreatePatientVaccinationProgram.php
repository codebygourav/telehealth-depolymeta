<?php

namespace App\Filament\Resources\PatientVaccinationPrograms\Pages;

use App\Filament\Resources\PatientVaccinationPrograms\PatientVaccinationProgramResource;
use Filament\Resources\Pages\CreateRecord;
use Filament\Support\Enums\Alignment;

class CreatePatientVaccinationProgram extends CreateRecord
{
    protected static string $resource = PatientVaccinationProgramResource::class;

    public function getFormActionsAlignment(): Alignment|string
    {
        return Alignment::End;
    }
}
