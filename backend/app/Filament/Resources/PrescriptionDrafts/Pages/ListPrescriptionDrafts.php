<?php

namespace App\Filament\Resources\PrescriptionDrafts\Pages;

use App\Filament\Resources\PrescriptionDrafts\PrescriptionDraftResource;
use App\Filament\Resources\Pages\ListRecords;

class ListPrescriptionDrafts extends ListRecords
{
    protected static string $resource = PrescriptionDraftResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
