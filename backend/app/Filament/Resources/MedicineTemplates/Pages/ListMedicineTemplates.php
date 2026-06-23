<?php

namespace App\Filament\Resources\MedicineTemplates\Pages;

use App\Filament\Resources\MedicineTemplates\MedicineTemplateResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\ListRecords;

class ListMedicineTemplates extends ListRecords
{
    protected static string $resource = MedicineTemplateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('create')
                ->label('Create Template')
                ->icon('heroicon-o-plus')
                ->url(fn() => MedicineTemplateResource::getUrl('create'))
                ->visible(fn() => MedicineTemplateResource::canCreate()),
        ];
    }
}
