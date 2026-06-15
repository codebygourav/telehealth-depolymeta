<?php

namespace App\Filament\Resources\PatientVaccinations\Pages;

use App\Filament\Resources\PatientVaccinations\PatientVaccinationResource;
use Filament\Actions\CreateAction;
use Filament\Actions\Action;
use Filament\Resources\Pages\ListRecords;

class ListPatientVaccinations extends ListRecords
{
    protected static string $resource = PatientVaccinationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('create')
                ->label('Create')
                ->icon('heroicon-o-plus')
                ->url(fn() => PatientVaccinationResource::getUrl('create')),
        ];
    }
}
