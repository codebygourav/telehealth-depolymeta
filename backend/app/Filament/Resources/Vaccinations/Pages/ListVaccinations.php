<?php

namespace App\Filament\Resources\Vaccinations\Pages;

use App\Filament\Resources\Vaccinations\VaccinationResource;
use Filament\Actions\CreateAction;
use Filament\Actions\Action;
use Filament\Resources\Pages\ListRecords;

class ListVaccinations extends ListRecords
{
    protected static string $resource = VaccinationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('create')
                ->label('Create')
                ->icon('heroicon-o-plus')
                ->url(fn() => VaccinationResource::getUrl('create'))
                ->visible(fn() => VaccinationResource::canCreate() || auth()->user()?->hasAnyRole(['super_admin', 'doctor_manager', 'receptionist', 'doctor'])),
        ];
    }
}
