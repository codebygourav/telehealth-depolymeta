<?php

namespace App\Filament\Resources\MedicineTypes\Pages;

use App\Filament\Resources\MedicineTypes\MedicineTypeResource;
use Filament\Resources\Pages\CreateRecord;
use Filament\Support\Enums\Alignment;

class CreateMedicineType extends CreateRecord
{
    protected static string $resource = MedicineTypeResource::class;

    public function getFormActionsAlignment(): Alignment|string
    {
        return Alignment::End;
    }
}
