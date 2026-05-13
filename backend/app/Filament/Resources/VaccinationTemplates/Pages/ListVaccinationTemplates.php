<?php

namespace App\Filament\Resources\VaccinationTemplates\Pages;

use App\Filament\Resources\VaccinationTemplates\VaccinationTemplateResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListVaccinationTemplates extends ListRecords
{
    protected static string $resource = VaccinationTemplateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->slideOver()
                ->visible(fn () => VaccinationTemplateResource::canCreate()),
        ];
    }
}
