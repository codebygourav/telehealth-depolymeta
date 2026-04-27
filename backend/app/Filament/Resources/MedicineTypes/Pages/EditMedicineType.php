<?php

namespace App\Filament\Resources\MedicineTypes\Pages;

use App\Filament\Resources\MedicineTypes\MedicineTypeResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Enums\Alignment;

class EditMedicineType extends EditRecord
{
    protected static string $resource = MedicineTypeResource::class;
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
