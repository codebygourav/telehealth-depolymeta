<?php

namespace App\Filament\Resources\MedicineTypes\Pages;

use Filament\Support\Enums\{Width, Alignment};
use App\Filament\Resources\MedicineTypes\MedicineTypeResource;
use Filament\Actions\CreateAction;
use App\Filament\Resources\Pages\ListRecords;

class ListMedicineTypes extends ListRecords
{
    protected static string $resource = MedicineTypeResource::class;

    public function getFormActionsAlignment(): Alignment|string
    {
        // Make header actions (the button) float to the right
        return Alignment::End;
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->modal()
                ->modalWidth(Width::Medium)
                ->modalSubmitActionLabel('Create')
                ->modalCancelActionLabel('Cancel')
                ->modalFooterActionsAlignment(Alignment::End)
                ->disableCreateAnother(),
        ];
    }
}
