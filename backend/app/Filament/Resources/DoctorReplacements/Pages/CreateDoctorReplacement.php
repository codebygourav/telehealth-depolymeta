<?php

namespace App\Filament\Resources\DoctorReplacements\Pages;

use App\Filament\Resources\DoctorReplacements\DoctorReplacementResource;
use App\Models\DoctorReplacement;
use App\Services\DoctorReplacementService;
use Filament\Resources\Pages\CreateRecord;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Model;

class CreateDoctorReplacement extends CreateRecord
{
    protected static string $resource = DoctorReplacementResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['replaced_by'] = \Illuminate\Support\Facades\Auth::id();
        return $data;
    }

    protected function handleRecordCreation(array $data): Model
    {
        $replacementService = app(DoctorReplacementService::class);
        $replacement = $replacementService->createReplacement($data);

        Notification::make()
            ->title('Replacement Created')
            ->body('Doctor replacement has been set up successfully. Appointments and availability have been transferred to the replacement doctor.')
            ->success()
            ->send();

        return $replacement;
    }
}
