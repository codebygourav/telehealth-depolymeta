<?php

namespace App\Filament\Resources\Users\Tables;

use App\Filament\Resources\Users\UserResource;
use App\Models\User;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

use function App\Helpers\getUserAuditColumn;

class UsersTable
{
    public static function configure(Table $table): Table
    {

        // If the user doesn't have access, show empty table with "Access Denied"
        if (! User::canUserAccess()) {
            return $table
                ->columns([])
                ->filters([])
                ->recordActions([])
                ->toolbarActions([])
                ->query(fn($query) => $query->whereRaw('1 = 0'))
                ->emptyStateHeading('Access Denied')
                ->emptyStateDescription('You do not have permission to view users.')
                ->emptyStateIcon('heroicon-o-lock-closed');
        }

        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Name')
                    ->searchable()
                    ->sortable()
                    ->icon('heroicon-o-user')
                    ->iconColor(' text-primary')
                    ->formatStateUsing(fn($state) => $state ?? 'Unknown'),

                TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->copyMessage('Email copied')
                    ->icon('heroicon-o-envelope')
                    ->iconColor(' text-primary')
                    ->formatStateUsing(fn($state) => $state ?? 'Unknown'),

                TextColumn::make('phone')
                    ->label('Phone')
                    ->searchable()
                    ->toggleable()
                    ->icon('heroicon-o-phone')
                    ->iconColor(' text-primary')
                    ->formatStateUsing(fn($state) => $state ?? 'Unknown'),

                TextColumn::make('roles.name')
                    ->label('Roles')
                    ->badge()
                    ->separator(',')
                    ->color(fn($state) => $state ? 'su' : ' text-gray-500 bg-gray-200')
                    ->formatStateUsing(function ($state) {
                        if (! $state) {
                            return 'Unknown';
                        }
                        return collect(explode(',', $state))
                            ->map(fn($role) => ucwords(str_replace('_', ' ', $role)))
                            ->join(', ');
                    })
                    ->tooltip(
                        fn($record) => $record->roles?->pluck('name')
                            ->map(fn($name) => ucwords(str_replace('_', ' ', $name)))
                            ->join(', ') ?: 'Unknown'
                    )
                    ->color('primary'),
                TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime('M j, Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->formatStateUsing(fn($state) => $state ? $state->format('M j, Y') : 'Unknown'),

                TextColumn::make('deleted_at')
                    ->label('Deleted')
                    ->dateTime('M j, Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->formatStateUsing(fn($state) => $state ? $state->format('M j, Y') : 'Unknown'),
                getUserAuditColumn('creator', 'Created By'),
                getUserAuditColumn('updater', 'Updated By'),
                getUserAuditColumn('deleter', 'Deleted By'),
            ])
            ->filters([
                SelectFilter::make('roles')
                    ->label('User Role')
                    ->multiple()
                    ->relationship('roles', 'name')
                    ->options(
                        \Spatie\Permission\Models\Role::pluck('name', 'name')
                            ->mapWithKeys(fn($value, $key) => [
                                $key => ucwords(str_replace('_', ' ', $value)),
                            ])
                            ->toArray()
                    ),
            ])
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make()
                        ->icon('heroicon-o-eye')
                        ->visible(fn($record) => UserResource::canView($record)),

                    EditAction::make()
                        ->icon('heroicon-o-pencil')
                        ->visible(fn($record) => UserResource::canEdit($record)),

                    DeleteAction::make()
                        ->icon('heroicon-o-trash')
                        ->requiresConfirmation()
                        ->visible(fn($record) => UserResource::canDelete($record)),
                ])
                    ->icon('heroicon-o-ellipsis-vertical')
                    ->color('text-primary')
                    ->label('Actions'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->requiresConfirmation()
                        ->visible(fn() => UserResource::canDelete(null)),
                    RestoreBulkAction::make()
                        ->visible(fn() => UserResource::canEdit(null)),
                    ForceDeleteBulkAction::make()
                        ->requiresConfirmation()
                        ->visible(fn() => UserResource::canDelete(null)),
                ]),
            ])

            ->defaultSort('created_at', 'desc')
            ->emptyStateHeading('Unknown')
            ->emptyStateDescription('No users data found. Showing sample data.')
            ->emptyStateIcon('heroicon-o-users')
            ->striped()
            ->paginated([5, 10, 25, 50, 100])
            ->extraAttributes([
                'class' => 'custom-pagination',
            ])
            ->recordUrl(null);
    }
}
