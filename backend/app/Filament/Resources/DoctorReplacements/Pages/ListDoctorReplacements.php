<?php

namespace App\Filament\Resources\DoctorReplacements\Pages;

use App\Filament\Resources\DoctorReplacements\DoctorReplacementResource;
use Filament\Actions\CreateAction;
use App\Filament\Resources\Pages\ListRecords;

class ListDoctorReplacements extends ListRecords
{
    protected static string $resource = DoctorReplacementResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
