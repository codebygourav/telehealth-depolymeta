<?php

namespace App\Support;

use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class FilamentUiVisibility
{
    public static function canAccessClass(?string $class): bool
    {
        if (! is_string($class) || ! class_exists($class)) {
            return false;
        }

        if (method_exists($class, 'canViewAny') && ! $class::canViewAny()) {
            return false;
        }

        if (method_exists($class, 'shouldRegisterNavigation') && ! $class::shouldRegisterNavigation()) {
            return false;
        }

        if (method_exists($class, 'canAccess') && ! $class::canAccess()) {
            return false;
        }

        return true;
    }

    public static function canShowResourceCreate(?string $resourceClass): bool
    {
        if (! is_string($resourceClass) || ! class_exists($resourceClass)) {
            return false;
        }

        if (! method_exists($resourceClass, 'canCreate')) {
            return true;
        }

        return (bool) $resourceClass::canCreate();
    }

    public static function canViewResourceIndex(?string $resourceClass): bool
    {
        if (! is_string($resourceClass) || ! class_exists($resourceClass)) {
            return false;
        }

        if (! method_exists($resourceClass, 'canViewAny')) {
            return true;
        }

        return (bool) $resourceClass::canViewAny();
    }

    public static function canShowHeaderAction(object $livewire, mixed $action): bool
    {
        if (! $action instanceof CreateAction) {
            return true;
        }

        if (! method_exists($livewire, 'getResource')) {
            return true;
        }

        return static::canShowResourceCreate($livewire::getResource());
    }

    public static function canShowSettingsLink(): bool
    {
        return \App\Filament\Pages\Settings::canAccess();
    }

    public static function prepareActions(array $actions, ?string $resourceClass = null): array
    {
        $prepared = [];

        foreach ($actions as $action) {
            if ($action instanceof ActionGroup) {
                $action->actions(static::prepareActions($action->getActions(), $resourceClass));
                $prepared[] = $action;

                continue;
            }

            if ($action instanceof Action) {
                static::applyResourceAuthorization($action, $resourceClass);
            }

            $prepared[] = $action;
        }

        return $prepared;
    }

    public static function prepareTable(Table $table, ?string $resourceClass = null): Table
    {
        $table->headerActions(static::prepareActions($table->getHeaderActions(), $resourceClass), $table->getHeaderActionsPosition());
        $table->toolbarActions(static::prepareActions($table->getToolbarActions(), $resourceClass));
        $table->recordActions(static::prepareActions($table->getRecordActions(), $resourceClass), $table->getRecordActionsPosition());
        $table->emptyStateActions(static::prepareActions($table->getEmptyStateActions(), $resourceClass), shouldOverwriteExistingActions: true);

        return $table;
    }

    public static function denyTableAccess(Table $table, string $description): Table
    {
        return $table
            ->columns([])
            ->filters([])
            ->recordActions([])
            ->toolbarActions([])
            ->query(fn (Builder $query) => $query->whereRaw('1 = 0'))
            ->emptyStateHeading('Access Denied')
            ->emptyStateDescription($description)
            ->emptyStateIcon('heroicon-o-lock-closed');
    }

    protected static function applyResourceAuthorization(Action $action, ?string $resourceClass): void
    {
        if (! is_string($resourceClass) || ! class_exists($resourceClass)) {
            return;
        }

        match (true) {
            $action instanceof CreateAction => $action->authorize(fn (): bool => $resourceClass::canCreate()),
            $action instanceof ViewAction => $action->authorize(fn ($record = null): bool => $record ? $resourceClass::canView($record) : false),
            $action instanceof EditAction => $action->authorize(fn ($record = null): bool => $record ? $resourceClass::canEdit($record) : false),
            $action instanceof DeleteAction => $action->authorize(fn ($record = null): bool => $record ? $resourceClass::canDelete($record) : false),
            $action instanceof ForceDeleteAction => $action->authorize(fn ($record = null): bool => $record ? $resourceClass::canForceDelete($record) : false),
            $action instanceof RestoreAction => $action->authorize(fn ($record = null): bool => $record ? $resourceClass::canRestore($record) : false),
            $action instanceof DeleteBulkAction => $action->authorize(fn (): bool => $resourceClass::canDeleteAny()),
            $action instanceof ForceDeleteBulkAction => $action->authorize(fn (): bool => $resourceClass::canForceDeleteAny()),
            $action instanceof RestoreBulkAction => $action->authorize(fn (): bool => $resourceClass::canRestoreAny()),
            default => null,
        };
    }
}
