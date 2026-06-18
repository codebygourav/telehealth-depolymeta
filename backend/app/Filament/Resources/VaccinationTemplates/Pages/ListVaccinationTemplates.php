<?php

namespace App\Filament\Resources\VaccinationTemplates\Pages;

use App\Filament\Resources\VaccinationTemplates\VaccinationTemplateResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\ListRecords;

class ListVaccinationTemplates extends ListRecords
{
    protected static string $resource = VaccinationTemplateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('create')
                ->label('Create New Template')
                ->icon('heroicon-o-plus')
                ->url(fn() => VaccinationTemplateResource::getUrl('create'))
                ->visible(fn() => VaccinationTemplateResource::canCreate()),
        ];
    }
}
