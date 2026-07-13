<?php

namespace App\Filament\Resources\Patients\Tables;

use App\Filament\Resources\Patients\PatientResource;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
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
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
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

                TextColumn::make('existing_patient_id')
                    ->label('Patient ID')
                    ->searchable()
                    ->sortable()
                    ->placeholder('-'),

                TextColumn::make('mobile_no')
                    ->label('Mobile')
                    ->searchable()
                    ->sortable(),

                BadgeColumn::make('source')
                    ->sortable()
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        'app' => 'Mobile App',
                        'website' => 'Website',
                        'internal' => 'Internal',
                        default => ucfirst((string) $state),
                    })
                    ->colors([
                        'info' => 'website',
                        'success' => 'app',
                        'gray' => 'internal',
                    ]),

                BadgeColumn::make('login_status')
                    ->label('Login')
                    // Removed ->sortable() to prevent attempting to sort by a non-existent column
                    ->getStateUsing(function ($record) {
                        if (! $record->user_id || ! $record->user) {
                            return 'Disabled';
                        }

                        $status = $record->user->status instanceof \App\Enums\AuthStatus
                            ? $record->user->status->value
                            : $record->user->status;

                        return $record->user->email_verified_at && $status === \App\Enums\AuthStatus::registered->value
                            ? 'Verified'
                            : 'Pending';
                    })
                    ->colors([
                        'success' => 'Verified',
                        'warning' => 'Pending',
                        'gray' => 'Disabled',
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
                Filter::make('search')
                    ->form([
                        TextInput::make('query')
                            ->label('Search')
                            ->placeholder('Search by name, email, mobile, or patient ID'),
                    ])
                    ->query(function ($query, array $data) {
                        if (blank($data['query'] ?? null)) {
                            return $query;
                        }

                        $search = trim($data['query']);

                        return $query->where(function ($patientQuery) use ($search) {
                            $patientQuery
                                ->where('first_name', 'like', "%{$search}%")
                                ->orWhere('last_name', 'like', "%{$search}%")
                                ->orWhereRaw("CONCAT(first_name, ' ', last_name) like ?", ["%{$search}%"])
                                ->orWhere('email', 'like', "%{$search}%")
                                ->orWhere('mobile_no', 'like', "%{$search}%")
                                ->orWhere('alternate_no', 'like', "%{$search}%")
                                ->orWhere('existing_patient_id', 'like', "%{$search}%")
                                ->orWhereHas('user', function ($userQuery) use ($search) {
                                    $userQuery
                                        ->where('name', 'like', "%{$search}%")
                                        ->orWhere('email', 'like', "%{$search}%")
                                        ->orWhere('phone', 'like', "%{$search}%");
                                });
                        });
                    }),
                SelectFilter::make('source')
                    ->label('Source')
                    ->options([
                        'app' => 'Mobile App',
                        'website' => 'Website',
                        'internal' => 'Internal',
                    ]),
                SelectFilter::make('login_status')
                    ->label('Login Status')
                    ->options([
                        'verified' => 'Verified',
                        'pending' => 'Pending',
                        'disabled' => 'Disabled',
                    ])
                    ->query(function ($query, array $data) {
                        return match ($data['value'] ?? null) {
                            'verified' => $query->whereHas('user', function ($userQuery) {
                                $userQuery
                                    ->whereNotNull('email_verified_at')
                                    ->where('status', \App\Enums\AuthStatus::registered->value);
                            }),
                            'pending' => $query->whereHas('user', function ($userQuery) {
                                $userQuery->where(function ($innerQuery) {
                                    $innerQuery
                                        ->whereNull('email_verified_at')
                                        ->orWhere('status', '!=', \App\Enums\AuthStatus::registered->value);
                                });
                            }),
                            'disabled' => $query->where(function ($patientQuery) {
                                $patientQuery
                                    ->whereNull('user_id')
                                    ->orWhereDoesntHave('user')
                                    ->orWhereHas('user', function ($userQuery) {
                                        $userQuery->where(function ($innerQuery) {
                                            $innerQuery
                                                ->whereNull('email_verified_at')
                                                ->orWhere('status', '!=', \App\Enums\AuthStatus::registered->value);
                                        });
                                    });
                            }),
                            default => $query,
                        };
                    }),
                TernaryFilter::make('is_existing_patient')
                    ->label('Existing Patient')
                    ->placeholder('All patients')
                    ->trueLabel('Existing patients only')
                    ->falseLabel('New patients only'),
                Filter::make('created_at')
                    ->label('Created Date')
                    ->form([
                        DatePicker::make('date')
                            ->label('Created Date'),
                    ])
                    ->query(function ($query, array $data) {
                        if (blank($data['date'] ?? null)) {
                            return $query;
                        }

                        return $query->whereDate('created_at', $data['date']);
                    }),
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
                        ->requiresConfirmation()
                        ->visible(fn () => PatientResource::canDelete(null)),
                    RestoreBulkAction::make()
                        ->visible(fn () => check_permission('patients.delete_any')),
                    ForceDeleteBulkAction::make()
                        ->visible(fn () => check_permission('patients.delete_any')),
                ]),
            ])
            ->extraAttributes([
                'class' => 'custom-pagination',
            ])
            ->recordUrl(null);
    }
}
