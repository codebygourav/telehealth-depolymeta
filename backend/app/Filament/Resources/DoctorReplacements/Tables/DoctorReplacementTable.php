<?php

namespace App\Filament\Resources\DoctorReplacements\Tables;

use App\Models\DoctorReplacement;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;

class DoctorReplacementTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('originalDoctor.user.name')
                    ->label('Original Doctor')
                    ->searchable(['originalDoctor.first_name', 'originalDoctor.last_name'])
                    ->sortable(),
                TextColumn::make('replacementDoctor.user.name')
                    ->label('Replacement Doctor')
                    ->searchable(['replacementDoctor.first_name', 'replacementDoctor.last_name'])
                    ->sortable(),
                BadgeColumn::make('replacement_type')
                    ->label('Type')
                    ->formatStateUsing(fn(string $state): string => match ($state) {
                        'single' => 'Single',
                        'selected' => 'Selected',
                        'all' => 'All',
                        'permanent' => 'Permanent',
                        default => $state,
                    })
                    ->colors([
                        'primary' => 'single',
                        'success' => 'selected',
                        'warning' => 'all',
                        'danger' => 'permanent',
                    ]),
                TextColumn::make('start_date')
                    ->label('Start Date')
                    ->date('d M Y')
                    ->sortable(),
                TextColumn::make('end_date')
                    ->label('End Date')
                    ->date('d M Y')
                    ->sortable(),
                TextColumn::make('reason')
                    ->label('Reason')
                    ->searchable(),
                BadgeColumn::make('is_active')
                    ->label('Status')
                    ->formatStateUsing(fn(bool $state): string => $state ? 'Active' : 'Inactive')
                    ->colors([
                        'success' => true,
                        'danger' => false,
                    ]),
                TextColumn::make('affected_appointments_count')
                    ->label('Affected Appointments')
                    ->getStateUsing(function (DoctorReplacement $record) {
                        return $record->affectedAppointments()->count();
                    })
                    ->badge(),
                TextColumn::make('transferred_availabilities_count')
                    ->label('Transferred Availabilities')
                    ->getStateUsing(function (DoctorReplacement $record) {
                        return $record->getTransferredAvailabilitiesCountAttribute();
                    })
                    ->badge()
                    ->color('info'),
                TextColumn::make('replacedByUser.name')
                    ->label('Replaced By')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label('Created At')
                    ->dateTime('d M Y, h:i A')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('replacement_type')
                    ->options([
                        'single' => 'Single',
                        'selected' => 'Selected',
                        'all' => 'All',
                        'permanent' => 'Permanent',
                    ]),
                SelectFilter::make('is_active')
                    ->label('Status')
                    ->options([
                        true => 'Active',
                        false => 'Inactive',
                    ]),
            ])
            ->actions([
                Action::make('view')
                    ->label('View')
                    ->icon('heroicon-o-eye')
                    ->url(fn(DoctorReplacement $record): string => route('filament.admin.resources.doctor-replacements.view', $record)),
                Action::make('revert')
                    ->label('Revert')
                    ->icon('heroicon-o-arrow-uturn-left')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Revert Replacement')
                    ->modalDescription('Are you sure you want to revert this replacement? All affected appointments will be restored to the original doctor.')
                    ->action(function (DoctorReplacement $record) {
                        $record->revert();

                        \Filament\Notifications\Notification::make()
                            ->title('Replacement Reverted')
                            ->body('Appointments and availability have been restored to the original doctor.')
                            ->success()
                            ->send();
                    })
                    ->visible(fn(DoctorReplacement $record) => $record->is_active),
            ])
            ->bulkActions([
                BulkAction::make('revert')
                    ->label('Revert Selected')
                    ->icon('heroicon-o-arrow-uturn-left')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->action(function ($records) {
                        foreach ($records as $record) {
                            if ($record->is_active) {
                                $record->revert();
                            }
                        }

                        \Filament\Notifications\Notification::make()
                            ->title('Replacements Reverted')
                            ->body('All selected replacements have been reverted. Appointments and availability have been restored.')
                            ->success()
                            ->send();
                    }),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
