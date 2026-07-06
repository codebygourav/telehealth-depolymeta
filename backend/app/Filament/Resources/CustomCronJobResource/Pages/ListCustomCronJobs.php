<?php

namespace App\Filament\Resources\CustomCronJobResource\Pages;

use App\Filament\Resources\CustomCronJobResource;
use Filament\Actions\CreateAction;
use App\Filament\Resources\Pages\ListRecords;

class ListCustomCronJobs extends ListRecords
{
    protected static string $resource = CustomCronJobResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->modal(),
        ];
    }
}
