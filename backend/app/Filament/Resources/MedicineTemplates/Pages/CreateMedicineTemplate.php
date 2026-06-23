<?php

namespace App\Filament\Resources\MedicineTemplates\Pages;

use App\Filament\Resources\MedicineTemplates\MedicineTemplateResource;
use Filament\Resources\Pages\CreateRecord;
use Filament\Support\Enums\Alignment;

class CreateMedicineTemplate extends CreateRecord
{
    protected static string $resource = MedicineTemplateResource::class;

    public function getFormActionsAlignment(): Alignment|string
    {
        return Alignment::End;
    }
}
