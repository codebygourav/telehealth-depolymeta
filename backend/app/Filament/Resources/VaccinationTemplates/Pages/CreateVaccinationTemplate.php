<?php

namespace App\Filament\Resources\VaccinationTemplates\Pages;

use App\Filament\Resources\VaccinationTemplates\VaccinationTemplateResource;
use Filament\Resources\Pages\CreateRecord;
use Filament\Support\Enums\Alignment;

class CreateVaccinationTemplate extends CreateRecord
{
    protected static string $resource = VaccinationTemplateResource::class;

    public function getFormActionsAlignment(): Alignment|string
    {
        return Alignment::End;
    }
}
