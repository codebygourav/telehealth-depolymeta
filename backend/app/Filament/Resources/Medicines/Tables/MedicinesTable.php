<?php

namespace App\Filament\Resources\Medicines\Tables;

use Filament\Actions\{BulkActionGroup, DeleteBulkAction, DeleteAction, EditAction, ForceDeleteBulkAction, RestoreBulkAction, ActionGroup};
use Filament\Tables\Table;
use App\Filament\Resources\Medicines\MedicineResource;
use Filament\Tables\Columns\TextColumn;
use function App\Helpers\getUserAuditColumn;
use Filament\Tables\Filters\TrashedFilter;

class MedicinesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name'),
                TextColumn::make('category.name'),
                TextColumn::make('type.name'),
                TextColumn::make('created_at'),
                TextColumn::make('updated_at'),
                getUserAuditColumn('creator', 'Created By'),
                getUserAuditColumn('updater', 'Updated By'),
                getUserAuditColumn('deleter', 'Deleted By'),
            ])
            ->filters([
                TrashedFilter::make(),
            ])
            ->recordActions([
                ActionGroup::make([
                    EditAction::make()
                        ->slideOver()
                        ->visible(fn($record) => MedicineResource::canEditRecord($record)),

                    DeleteAction::make()
                        ->visible(fn($record) => MedicineResource::canDeleteRecord($record)),
                ])
            ])

            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->requiresConfirmation()
                        ->visible(fn() => MedicineResource::canDeleteRecord(null)),
                    RestoreBulkAction::make()
                        ->visible(fn() => MedicineResource::canEditRecord(null)),
                    ForceDeleteBulkAction::make()
                        ->requiresConfirmation()
                        ->visible(fn() => MedicineResource::canDeleteRecord(null)),
                ]),
            ])
            ->recordUrl(null);
    }
}
