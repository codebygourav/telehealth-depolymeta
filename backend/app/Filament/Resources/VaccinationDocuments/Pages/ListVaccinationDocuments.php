<?php

namespace App\Filament\Resources\VaccinationDocuments\Pages;

use App\Filament\Resources\VaccinationDocuments\VaccinationDocumentResource;
use Filament\Actions\CreateAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\ListRecords;

class ListVaccinationDocuments extends ListRecords
{
    protected static string $resource = VaccinationDocumentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->slideOver(),
        ];
    }
}
