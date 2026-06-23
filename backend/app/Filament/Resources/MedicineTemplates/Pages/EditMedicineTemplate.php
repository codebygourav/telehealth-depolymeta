<?php

namespace App\Filament\Resources\MedicineTemplates\Pages;

use App\Filament\Resources\MedicineTemplates\MedicineTemplateResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Enums\Alignment;

class EditMedicineTemplate extends EditRecord
{
    protected static string $resource = MedicineTemplateResource::class;

    protected string $view = 'filament.resources.medicine-templates.pages.edit-medicine-template';

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
