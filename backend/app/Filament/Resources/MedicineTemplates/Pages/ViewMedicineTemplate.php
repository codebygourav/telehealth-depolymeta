<?php

namespace App\Filament\Resources\MedicineTemplates\Pages;

use App\Filament\Resources\MedicineTemplates\MedicineTemplateResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewMedicineTemplate extends ViewRecord
{
    protected static string $resource = MedicineTemplateResource::class;

    protected string $view = 'filament.resources.medicine-templates.pages.view-medicine-template';

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()
                ->label('Edit Template')
                ->icon('heroicon-o-pencil-square'),
        ];
    }
}
