<?php

namespace App\Filament\Resources\VaccinationDocuments\Pages;

use App\Filament\Resources\VaccinationDocuments\VaccinationDocumentResource;
use Filament\Resources\Pages\ViewRecord;

class ViewVaccinationDocument extends ViewRecord
{
    protected static string $resource = VaccinationDocumentResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
