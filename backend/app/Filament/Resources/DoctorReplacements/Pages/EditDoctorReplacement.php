<?php

namespace App\Filament\Resources\DoctorReplacements\Pages;

use App\Filament\Resources\DoctorReplacements\DoctorReplacementResource;
use Filament\Resources\Pages\EditRecord;

class EditDoctorReplacement extends EditRecord
{
    protected static string $resource = DoctorReplacementResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Add revert action if needed
        ];
    }
}
