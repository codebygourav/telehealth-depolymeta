<?php

namespace App\Filament\Resources\DietTemplates\Pages;

use App\Filament\Resources\DietTemplates\DietTemplateResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\ListRecords;

class ListDietTemplates extends ListRecords
{
    protected static string $resource = DietTemplateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('create')
                ->label('Create')
                ->icon('heroicon-o-plus')
                ->url(fn() => DietTemplateResource::getUrl('create'))
                ->visible(fn() => DietTemplateResource::canCreate()),
        ];
    }
}
