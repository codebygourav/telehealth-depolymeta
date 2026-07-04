<?php

namespace App\Filament\Resources\DisplayScreens\Pages;

use App\Filament\Resources\DisplayScreens\DisplayScreenResource;
use App\Filament\Resources\Pages\ListRecords;
use Filament\Actions\CreateAction;

class ListDisplayScreens extends ListRecords
{
    protected static string $resource = DisplayScreenResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
