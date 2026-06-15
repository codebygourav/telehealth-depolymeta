<?php

namespace App\Filament\Resources\DietTemplates\Pages;

use App\Filament\Resources\DietTemplates\DietTemplateResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Enums\Alignment;

class EditDietTemplate extends EditRecord
{
    protected static string $resource = DietTemplateResource::class;

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
