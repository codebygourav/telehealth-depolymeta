<?php

namespace App\Filament\Resources\MedicineTypes\Tables;

use App\Filament\Resources\MedicineTypes\MedicineTypeResource;
use Filament\Actions\{BulkActionGroup, DeleteBulkAction, EditAction, ForceDeleteBulkAction, RestoreBulkAction};
use Filament\Support\Enums\{Alignment, Width};
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use function App\Helpers\getUserAuditColumn;

class MedicineTypesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->searchable(),
                TextColumn::make('name')
                    ->searchable(),
                TextColumn::make('slug')
                    ->searchable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                getUserAuditColumn('creator', 'Created By'),
                getUserAuditColumn('updater', 'Updated By'),
                getUserAuditColumn('deleter', 'Deleted By'),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make()
                    ->modal() // Use modal instead of slideOver in Filament v4
                    ->modalWidth(Width::Medium)
                    ->modalFooterActionsAlignment(Alignment::End),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->requiresConfirmation()
                        ->visible(fn() => MedicineTypeResource::canDelete(null)),
                    RestoreBulkAction::make()
                        ->visible(fn() => MedicineTypeResource::canEdit(null)),
                    ForceDeleteBulkAction::make()
                        ->requiresConfirmation()
                        ->visible(fn() => MedicineTypeResource::canDelete(null)),
                ]),
            ]);
    }
}
