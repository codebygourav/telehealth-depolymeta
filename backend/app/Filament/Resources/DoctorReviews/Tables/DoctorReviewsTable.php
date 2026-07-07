<?php

namespace App\Filament\Resources\DoctorReviews\Tables;

use App\Filament\Resources\DoctorReviews\DoctorReviewResource;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use function App\Helpers\getUserAuditColumn;
use Filament\Actions\{ActionGroup, BulkActionGroup, DeleteBulkAction, ForceDeleteBulkAction, RestoreBulkAction, ViewAction, DeleteAction, EditAction};
use Illuminate\Database\Eloquent\Builder;


class DoctorReviewsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('review_type')
                    ->label('Type')
                    ->badge()
                    ->color(fn($state) => match ($state) {
                        'original' => 'success',
                        'fake' => 'warning',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn($state) => match ($state) {
                        'original' => 'Original',
                        'fake' => 'Fake',
                        default => 'Unknown',
                    })
                    ->sortable()
                    ->searchable(),

                TextColumn::make('title')
                    ->searchable()
                    ->limit(50)
                    ->tooltip(fn($record) => $record->title)
                    ->weight('medium'),

                TextColumn::make('patient_name')
                    ->label('Patient')
                    ->sortable()
                    ->searchable(query: function (Builder $query, string $search): Builder {

                        return $query
                            ->whereHas('patient.user', function ($q) use ($search) {
                                $q->where('name', 'like', "%{$search}%");
                            })
                            ->orWhereHas('fakerPatient', function ($q) use ($search) {
                                $q->where('name', 'like', "%{$search}%");
                            });
                    })
                    ->getStateUsing(function ($record) {

                        // Real Patient
                        if ($record->patient?->user?->name) {
                            return $record->patient->user->name;
                        }

                        // Fake Patient
                        if ($record->fakerPatient?->name) {
                            return $record->fakerPatient->name;
                        }

                        return '-';
                    }),

                TextColumn::make('doctor.user.name')
                    ->label('Doctor')
                    ->searchable()
                    ->sortable()
                    ->default('-'),

                TextColumn::make('appointment.appointment_date')
                    ->label('Appointment')
                    ->formatStateUsing(fn($state) => $state ? \Carbon\Carbon::parse($state)->format('M d, Y') : '-')
                    ->description(fn($record) => $record->appointment ? "Time: {$record->appointment->appointment_time}" : null)
                    ->toggleable()
                    ->searchable(),

                TextColumn::make('rating')
                    ->label('Rating')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color(fn($state) => match ($state) {
                        5 => 'success',
                        4 => 'info',
                        3 => 'warning',
                        default => 'danger',
                    })
                    ->formatStateUsing(fn($state) => str_repeat('*', $state) . ' (' . $state . '/5)')
                    ->sortable(),

                IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean(),

                IconColumn::make('is_featured')
                    ->label('Featured')
                    ->boolean(),
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
                SelectFilter::make('review_type')
                    ->label('Review Type')
                    ->options([
                        'original' => 'Original Review',
                        'fake' => 'Fake Review',
                    ])
                    ->placeholder('All Review Types'),



                SelectFilter::make('rating_range')
                    ->label('Rating Range')
                    ->options([
                        'highest' => 'Excellent (5 stars only)',
                        'high' => 'Very Good (4-5 stars)',
                        'medium' => 'Average (3 stars only)',
                        'low' => 'Below Average (1-2 stars)',
                        'lowest' => 'Poor (1 star only)',
                    ])
                    ->placeholder('Any Rating')
                    ->query(function ($query, $data) {
                        $value = $data['value'] ?? null;
                        if (!$value) {
                            return $query;
                        }

                        return match ($value) {
                            'highest' => $query->where('rating', 5),
                            'high' => $query->whereIn('rating', [4, 5]),
                            'medium' => $query->where('rating', 3),
                            'low' => $query->whereIn('rating', [1, 2]),
                            'lowest' => $query->where('rating', 1),
                            default => $query,
                        };
                    }),

                SelectFilter::make('is_active')
                    ->label('Status')
                    ->options([
                        1 => 'Active',
                        0 => 'Inactive',
                    ])
                    ->placeholder('All Statuses'),

                SelectFilter::make('is_featured')
                    ->label('Featured')
                    ->options([
                        1 => 'Featured',
                        0 => 'Not Featured',
                    ])
                    ->placeholder('All Reviews'),

                SelectFilter::make('doctor_id')
                    ->label('Doctor')
                    ->relationship('doctor', 'first_name', fn($query) => $query->with('user'))
                    ->getOptionLabelFromRecordUsing(fn($record) => $record->first_name . ' ' . $record->last_name)
                    ->searchable()
                    ->preload()
                    ->placeholder('All Doctors'),
            ])
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make()
                        ->icon('heroicon-o-eye')
                        ->visible(fn($record) => DoctorReviewResource::canView($record)),

                    EditAction::make()
                        ->visible(fn($record) => DoctorReviewResource::canEdit($record)),

                    DeleteAction::make()
                        ->visible(fn($record) => DoctorReviewResource::canDelete($record)),
                ]),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->requiresConfirmation()
                        ->visible(fn() => DoctorReviewResource::canDelete(null)),
                    RestoreBulkAction::make()
                        ->visible(fn() => DoctorReviewResource::canEdit(null)),
                    ForceDeleteBulkAction::make()
                        ->requiresConfirmation()
                        ->visible(fn() => DoctorReviewResource::canDelete(null)),
                ]),
            ])
            ->recordUrl(null);
    }
}