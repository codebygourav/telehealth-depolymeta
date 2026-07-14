<?php

namespace App\Filament\Resources\Medicines\Pages;

use App\Filament\Resources\Medicines\MedicineResource;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Facades\FilamentView;

class EditMedicine extends EditRecord
{
    protected static string $resource = MedicineResource::class;

    protected string $view = 'filament.resources.medicines.pages.medicine-form';

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->visible(fn (): bool => MedicineResource::canDeleteRecord($this->record)),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }

    protected function getCancelFormAction(): Action
    {
        $url = $this->previousUrl ?? $this->getResourceUrl();

        return Action::make('cancel')
            ->label(__('filament-panels::resources/pages/edit-record.form.actions.cancel.label'))
            ->color('gray')
            ->requiresConfirmation()
            ->modalHeading('Cancel medicine changes?')
            ->modalDescription('Any unsaved medicine configuration, dosage options, aliases, and defaults will be lost.')
            ->modalSubmitActionLabel('Yes, cancel')
            ->modalCancelActionLabel('Keep editing')
            ->action(fn () => $this->redirect($url, navigate: FilamentView::hasSpaMode($url)));
    }
}
