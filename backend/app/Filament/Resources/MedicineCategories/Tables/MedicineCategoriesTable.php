<?php

namespace App\Filament\Resources\MedicineCategories\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Tables\Table;
use App\Filament\Resources\MedicineCategories\MedicineCategoryResource;
use Filament\Tables\Columns\TextColumn;
use function App\Helpers\getUserAuditColumn;

class MedicineCategoriesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name'),
                getUserAuditColumn('creator', 'Created By'),
                getUserAuditColumn('updater', 'Updated By'),
                getUserAuditColumn('deleter', 'Deleted By'),
            ])


            ->recordActions([
                EditAction::make()
                    ->slideOver()
                    ->visible(fn($record) => MedicineCategoryResource::canEdit($record)),

                DeleteAction::make()
                    ->visible(fn($record) => MedicineCategoryResource::canDelete($record)),
            ])

            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->requiresConfirmation()
                        ->visible(fn() => MedicineCategoryResource::canDelete(null)),
                    RestoreBulkAction::make()
                        ->visible(fn() => MedicineCategoryResource::canEdit(null)),
                    ForceDeleteBulkAction::make()
                        ->requiresConfirmation()
                        ->visible(fn() => MedicineCategoryResource::canDelete(null)),
                ]),
            ]);
    }
}
