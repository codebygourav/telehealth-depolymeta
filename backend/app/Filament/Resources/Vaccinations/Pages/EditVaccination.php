<?php

namespace App\Filament\Resources\Vaccinations\Pages;

use App\Filament\Resources\Vaccinations\VaccinationResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Enums\Alignment;

class EditVaccination extends EditRecord
{
    protected static string $resource = VaccinationResource::class;

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
