<?php

namespace App\Filament\Resources\VaccinationTemplates\Pages;

use App\Filament\Resources\VaccinationTemplates\VaccinationTemplateResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewVaccinationTemplate extends ViewRecord
{
    protected static string $resource = VaccinationTemplateResource::class;

    protected string $view = 'filament.resources.vaccination-templates.pages.view-vaccination-template';
    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
