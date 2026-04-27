<?php

namespace App\Filament\Resources\Doctors\Pages;

use App\Filament\Resources\Doctors\DoctorResource;
use Filament\Resources\Pages\ViewRecord;
use Filament\Actions\EditAction;

class ViewDoctor extends ViewRecord
{
    protected static string $resource = DoctorResource::class;
    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
