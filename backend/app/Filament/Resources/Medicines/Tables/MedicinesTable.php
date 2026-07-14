<?php

namespace App\Filament\Resources\Medicines\Tables;

use Filament\Actions\{BulkActionGroup, DeleteBulkAction, DeleteAction, EditAction, ForceDeleteBulkAction, RestoreBulkAction, ActionGroup};
use Filament\Tables\Table;
use App\Filament\Resources\Medicines\MedicineResource;
use Filament\Tables\Columns\TextColumn;
use function App\Helpers\getUserAuditColumn;

class MedicinesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('category.name')
                    ->label('Category')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('type.name')
                    ->label('Type')
                    ->badge()
                    ->searchable()
                    ->sortable(),
                TextColumn::make('spoken_aliases')
                    ->label('Voice aliases')
                    ->formatStateUsing(function ($state): string {
                        $aliases = collect(is_array($state) ? $state : [])
                            ->map(fn($val) => trim($val))
                            ->filter(fn($val) => $val !== '');
                        
                        if ($aliases->isEmpty()) {
                            return '';
                        }
                        
                        return $aliases->take(3)->implode(', ');
                    })
                    ->placeholder('No aliases')
                    ->searchable(query: function ($query, string $search) {
                        return $query->where('spoken_aliases', 'like', "%{$search}%");
                    })
                    ->toggleable(),
                TextColumn::make('speech_enabled')
                    ->label('Speech')
                    ->badge()
                    ->formatStateUsing(fn($state): string => $state ? 'Enabled' : 'Off')
                    ->color(fn($state): string => $state ? 'success' : 'gray')
                    ->toggleable(),
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
            ])
            ->recordActions([
                ActionGroup::make([
                    EditAction::make()
                        ->url(fn ($record): string => MedicineResource::getUrl('edit', ['record' => $record]))
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
