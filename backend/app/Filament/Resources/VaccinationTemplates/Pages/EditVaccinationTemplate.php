<?php

namespace App\Filament\Resources\VaccinationTemplates\Pages;

use App\Filament\Resources\VaccinationTemplates\VaccinationTemplateResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Enums\Alignment;

class EditVaccinationTemplate extends EditRecord
{
    protected static string $resource = VaccinationTemplateResource::class;

    protected string $view = 'filament.resources.vaccination-templates.pages.edit-vaccination-template';
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
