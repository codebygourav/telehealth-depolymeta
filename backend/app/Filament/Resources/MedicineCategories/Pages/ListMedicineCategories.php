<?php

namespace App\Filament\Resources\MedicineCategories\Pages;

use App\Filament\Resources\MedicineCategories\MedicineCategoryResource;
use Filament\Actions\CreateAction;
use App\Filament\Resources\Pages\ListRecords;

class ListMedicineCategories extends ListRecords
{
    protected static string $resource = MedicineCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->slideOver()
                ->visible(fn() => MedicineCategoryResource::canCreate() || auth()->user()?->hasRole('super_admin') || auth()->user()?->can('medicine-categories.create')),
        ];
    }
}
