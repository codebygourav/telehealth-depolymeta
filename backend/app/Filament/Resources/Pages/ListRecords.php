<?php

namespace App\Filament\Resources\Pages;

use App\Support\FilamentUiVisibility;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Tables\Table;
use Illuminate\Contracts\View\View;

abstract class ListRecords extends \Filament\Resources\Pages\ListRecords
{
    public function getFooter(): ?View
    {
        return view('filament.resources.list-layout');
    }

    public function cacheInteractsWithHeaderActions(): void
    {
        $actions = FilamentUiVisibility::prepareActions([
            ...$this->getGlobalHeaderActions(),
            ...$this->getHeaderActions(),
        ], static::getResource());

        foreach ($actions as $action) {
            if ($action instanceof ActionGroup) {
                $action->livewire($this);

                if (! $action->getDropdownPlacement()) {
                    $action->dropdownPlacement('bottom-end');
                }

                $this->mergeCachedActions($action->getFlatActions());
                $this->cachedHeaderActions[] = $action;

                continue;
            }

            $this->cacheAction($action);
            $this->cachedHeaderActions[] = $action;
        }
    }

    protected function getGlobalHeaderActions(): array
    {
        return [
            Action::make('resourceListBack')
                ->label('Back')
                ->icon('heroicon-o-arrow-left')
                ->url(fn(): string => url()->previous()),
        ];
    }

    public function getTabs(): array
    {
        return [];
    }

    protected function makeTable(): Table
    {
        return FilamentUiVisibility::prepareTable(parent::makeTable(), static::getResource());
    }
}
