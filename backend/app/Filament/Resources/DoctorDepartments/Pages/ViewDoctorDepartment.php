<?php

namespace App\Filament\Resources\DoctorDepartments\Pages;

use App\Filament\Resources\DoctorDepartments\DoctorDepartmentResource;
use Filament\Resources\Pages\ViewRecord;
use Filament\Actions\EditAction;


class ViewDoctorDepartment extends ViewRecord
{
    protected static string $resource = DoctorDepartmentResource::class;
    protected static ?string $breadcrumb = '';

     protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
