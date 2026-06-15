<?php

namespace App\Filament\Resources\Vaccinations\Pages;

use App\Filament\Resources\Vaccinations\VaccinationResource;
use Filament\Resources\Pages\CreateRecord;
use Filament\Support\Enums\Alignment;

class CreateVaccination extends CreateRecord
{
    protected static string $resource = VaccinationResource::class;

    public function getFormActionsAlignment(): Alignment|string
    {
        return Alignment::End;
    }
}
