<?php

namespace App\Filament\Resources\PatientVaccinationPrograms\Pages;

use App\Filament\Resources\PatientVaccinationPrograms\PatientVaccinationProgramResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Enums\Alignment;

class EditPatientVaccinationProgram extends EditRecord
{
    protected static string $resource = PatientVaccinationProgramResource::class;

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
