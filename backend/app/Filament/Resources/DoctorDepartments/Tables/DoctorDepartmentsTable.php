<?php

namespace App\Filament\Resources\DoctorDepartments\Tables;

use Filament\Tables\Table;
use Filament\Tables\Columns\{TextColumn, ImageColumn};
use Filament\Tables\Columns\ViewColumn;
use Filament\Actions\{ActionGroup};
use App\Filament\Resources\DoctorDepartments\DoctorDepartmentResource;
use Filament\Facades\Filament;
use Filament\Actions\{BulkActionGroup, BulkAction, ViewAction, EditAction, DeleteAction};
use function App\Helpers\getUserAuditColumn;

class DoctorDepartmentsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('department_featured')
                    ->label('Photo')
                    ->circular()
                    ->disk('public')
                    ->getStateUsing(fn($record) => storage_url($record->department_featured ?? $record->department_featured)),

                TextColumn::make('name')
                    ->label('Department')
                    ->html()
                    ->searchable(),
                TextColumn::make('symptoms')
                    ->label('Symptoms')
                    ->formatStateUsing(fn($state) => \App\Models\Symptom::whereIn('id', (array)$state)->pluck('name')->join(', '))
                    ->searchable(),

                ViewColumn::make('doctors_display')
                    ->label('Doctors')
                    ->view('filament.tables.columns.avatars-row'),
                TextColumn::make('is_tab_layout')
                    ->label('Tab Layout')
                    ->badge()
                    ->formatStateUsing(fn($state) => $state ? 'TRUE' : 'FALSE')
                    ->color(fn($state) => $state ? 'success' : 'danger'),

                TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime()
                    ->sortable(),

                TextColumn::make('updated_at')
                    ->label('Updated')
                    ->dateTime()
                    ->sortable(),
                getUserAuditColumn('creator', 'Created By'),
                getUserAuditColumn('updater', 'Updated By'),
                getUserAuditColumn('deleter', 'Deleted By'),
            ])

            ->filters([
            ])

            // Switched from ActionGroup to array of actions, since ActionGroup does not exist
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make()->visible(fn($record) => DoctorDepartmentResource::canView($record)),
                    EditAction::make()->visible(fn($record) => DoctorDepartmentResource::canEdit($record)),
                    DeleteAction::make()->visible(fn($record) => DoctorDepartmentResource::canDelete($record)),
                ]),
            ])

            ->bulkActions([
                BulkActionGroup::make([
                    BulkAction::make('delete')
                        ->label('Delete Selected')
                        ->icon('heroicon-o-trash')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->action(function ($records) {
                            $user = Filament::auth()->user();
                            foreach ($records as $record) {
                                // In Filament 4, you can check permissions or log the user action if needed
                                $record->delete();
                            }
                        })
                        ->visible(fn() => DoctorDepartmentResource::canDeleteAny() || auth()->user()?->hasRole('super_admin') || auth()->user()?->can('departments.delete_any')),
                    BulkAction::make('forceDelete')
                        ->label('Permanently Delete')
                        ->icon('heroicon-o-trash')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->action(function ($records) {
                            $user = Filament::auth()->user();
                            foreach ($records as $record) {
                                $record->forceDelete();
                            }
                        })
                        ->visible(fn() => DoctorDepartmentResource::canDeleteAny() || auth()->user()?->hasRole('super_admin') || auth()->user()?->can('departments.delete_any')),
                    BulkAction::make('restore')
                        ->label('Restore Selected')
                        ->icon('heroicon-o-arrow-path')
                        ->color('success')
                        ->action(function ($records) {
                            $user = Filament::auth()->user();
                            foreach ($records as $record) {
                                $record->restore();
                            }
                        })
                        ->visible(fn() => DoctorDepartmentResource::canDeleteAny() || auth()->user()?->hasRole('super_admin') || auth()->user()?->can('departments.delete_any')),
                ]),
            ])
            ->extraAttributes([
                'class' => 'custom-pagination',
            ])
            ->recordUrl(null);
    }
}
