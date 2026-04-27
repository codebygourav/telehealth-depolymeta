<?php

namespace App\Filament\Resources\Symptoms\Pages;

use App\Filament\Resources\Symptoms\SymptomResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewSymptom extends ViewRecord
{
    protected static string $resource = SymptomResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
