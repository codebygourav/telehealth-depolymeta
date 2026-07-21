<?php

namespace App\Filament\Pages;

use App\Support\FilamentUiVisibility;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;

abstract class Page extends \Filament\Pages\Page
{
    public function cacheInteractsWithHeaderActions(): void
    {
        $actions = FilamentUiVisibility::prepareActions($this->getHeaderActions());

        foreach ($actions as $action) {
            if ($action instanceof ActionGroup) {
                $action->livewire($this);

                if (! $action->getDropdownPlacement()) {
                    $action->dropdownPlacement('bottom-end');
                }

                /** @var array<string, Action> $flatActions */
                $flatActions = $action->getFlatActions();

                $this->mergeCachedActions($flatActions);
                $this->cachedHeaderActions[] = $action;

                continue;
            }

            $this->cacheAction($action);
            $this->cachedHeaderActions[] = $action;
        }
    }
}
