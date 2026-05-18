<?php

namespace App\Filament\Resources\DietTemplates\Pages;

use App\Filament\Resources\DietTemplates\DietTemplateResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListDietTemplates extends ListRecords
{
    protected static string $resource = DietTemplateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->slideOver()
                ->visible(fn () => DietTemplateResource::canCreate()),
        ];
    }
}
