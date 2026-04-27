<?php

namespace App\Filament\Resources\Symptoms\Tables;

use App\Filament\Resources\Symptoms\SymptomResource;
use Filament\Actions\{ActionGroup, BulkActionGroup, DeleteBulkAction, ForceDeleteBulkAction, RestoreBulkAction, ViewAction, DeleteAction, EditAction};
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use function App\Helpers\getUserAuditColumn;

class SymptomsTable
{
    public static function configure(Table $table): Table
    {
        /** @var \App\Models\User|null $user */
        $user = Auth::user();

        return $table
            ->columns([
                TextColumn::make('name')->searchable(),
                TextColumn::make('description')->searchable(),
                TextColumn::make('departments')
                    ->label('Departments')
                    ->getStateUsing(fn($record) => \App\Models\Department::whereJsonContains('symptom_ids', $record->id)->pluck('name')->join(', ')),
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

            ->recordActions([
                ActionGroup::make([
                    EditAction::make()
                        ->slideOver()
                        ->visible(
                            fn($record) =>
                            $user?->hasRole('super_admin') ||
                                $user?->can('symptoms.update') ||
                                $user?->can('symptoms.manage_own')
                        ),

                    DeleteAction::make()
                        ->visible(
                            fn($record) =>
                            $user?->hasRole('super_admin') ||
                                $user?->can('symptoms.delete') ||
                                $user?->can('symptoms.delete_any')
                        ),
                ])
            ])

            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->requiresConfirmation()
                        ->visible(fn() => SymptomResource::canDelete(null)),
                    RestoreBulkAction::make()
                        ->visible(fn() => SymptomResource::canEdit(null)),
                    ForceDeleteBulkAction::make()
                        ->requiresConfirmation()
                        ->visible(fn() => SymptomResource::canDelete(null)),
                ]),
            ]);
    }
}
