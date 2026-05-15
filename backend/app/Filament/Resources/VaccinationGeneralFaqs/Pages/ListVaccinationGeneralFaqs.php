<?php

namespace App\Filament\Resources\VaccinationGeneralFaqs\Pages;

use App\Filament\Resources\VaccinationGeneralFaqs\VaccinationGeneralFaqResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListVaccinationGeneralFaqs extends ListRecords
{
    protected static string $resource = VaccinationGeneralFaqResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->slideOver(),
        ];
    }
}
