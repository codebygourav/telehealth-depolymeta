<?php

namespace App\Filament\Resources\Patients\Tables;

use App\Filament\Resources\Patients\PatientResource;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

use function App\Helpers\getUserAuditColumn;

class PatientsTable
{
    public static function configure(Table $table): Table
    {
        // Only deny access for unauthenticated users. Any logged-in user
        // will be able to view the patients table. Actions (edit/delete)
        // are still gated by resource permission checks below.
        if (! Auth::check()) {
            return $table
                ->columns([])
                ->filters([])
                ->recordActions([])
                ->toolbarActions([])
                ->query(fn ($query) => $query->whereRaw('1 = 0'))
                ->emptyStateHeading('Access Denied')
                ->emptyStateDescription('You do not have permission to view patients.')
                ->emptyStateIcon('heroicon-o-lock-closed');
        }

        return $table
            ->columns([
                ImageColumn::make('avatar')
                    ->label('Photo')
                    ->circular()
                    ->getStateUsing(fn ($record) => storage_url($record->avatar ?? $record->user?->avatar)),
                TextColumn::make('full_name')
                    ->label('Name')
                    ->getStateUsing(fn ($record) => trim("{$record->first_name} {$record->last_name}"))
                    ->searchable(['first_name', 'last_name'])
                    ->sortable(['first_name', 'last_name']),

                TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->copyMessage('Email copied')
                    ->icon('heroicon-o-envelope')
                    ->iconColor(' text-primary'),

                TextColumn::make('gender')
                    ->label('Gender'),

                TextColumn::make('age')
                    ->numeric()
                    ->label('Age')
                    ->sortable(),

                TextColumn::make('mobile_no')
                    ->label('Mobile')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('city')
                    ->label('City')
                    ->searchable(),

                TextColumn::make('blood_group')
                    ->label('Blood Group'),

                BadgeColumn::make('source')
                    ->colors([
                        'info' => 'website',
                        'success' => 'app',
                    ]),

                TextColumn::make('created_at')
                    ->label('Created')
                    ->date('d M Y')
                    ->sortable()
                    ->toggleable(),

                getUserAuditColumn('creator', 'Created By'),
                getUserAuditColumn('updater', 'Updated By'),
                getUserAuditColumn('deleter', 'Deleted By'),

            ])
            ->filters([
                TrashedFilter::make(),
            ])
            ->recordActions([
                ActionGroup::make([
                    // Show Restore & Force Delete if record trashed
                    RestoreAction::make()
                        ->visible(fn ($record) => $record->trashed()),
                    ForceDeleteAction::make()
                        ->visible(fn ($record) => $record->trashed()),
                    // Show normal actions for non-trashed records
                    ViewAction::make()
                        ->icon('heroicon-o-eye')
                        ->visible(fn ($record) => PatientResource::canView($record) && ! $record->trashed()),
                    EditAction::make()
                        ->icon('heroicon-o-pencil')
                        ->visible(fn ($record) => PatientResource::canEdit($record) && ! $record->trashed()),
                    DeleteAction::make()
                        ->icon('heroicon-o-trash')
                        ->requiresConfirmation()
                        ->visible(fn ($record) => PatientResource::canDelete($record) && ! $record->trashed()),
                ])
                    ->icon('heroicon-o-ellipsis-vertical')
                    ->color('text-primary')
                    ->label('Actions'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->requiresConfirmation(),
                    RestoreBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                ]),
            ])
            ->extraAttributes([
                'class' => 'custom-pagination',
            ])
            ->recordUrl(null);
    }
}
