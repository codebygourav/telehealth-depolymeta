<?php

namespace App\Filament\Resources\Medicines\Pages;

use App\Filament\Resources\Medicines\MedicineResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\CreateRecord;
use Filament\Support\Facades\FilamentView;

class CreateMedicine extends CreateRecord
{
    protected static string $resource = MedicineResource::class;

    protected static bool $canCreateAnother = false;

    protected string $view = 'filament.resources.medicines.pages.medicine-form';

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('edit', ['record' => $this->record]);
    }

    protected function getCancelFormAction(): Action
    {
        $url = $this->previousUrl ?? $this->getResourceUrl();

        return Action::make('cancel')
            ->label(__('filament-panels::resources/pages/create-record.form.actions.cancel.label'))
            ->color('gray')
            ->requiresConfirmation()
            ->modalHeading('Cancel medicine setup?')
            ->modalDescription('Any unsaved medicine configuration, dosage options, aliases, and defaults will be lost.')
            ->modalSubmitActionLabel('Yes, cancel')
            ->modalCancelActionLabel('Keep editing')
            ->action(fn () => $this->redirect($url, navigate: FilamentView::hasSpaMode($url)));
    }
}
