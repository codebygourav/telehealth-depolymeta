<?php

namespace App\Filament\Resources\VaccinationClinicalInsights\Pages;

use App\Filament\Resources\VaccinationClinicalInsights\VaccinationClinicalInsightResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListVaccinationClinicalInsights extends ListRecords
{
    protected static string $resource = VaccinationClinicalInsightResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->slideOver(),
        ];
    }
}
