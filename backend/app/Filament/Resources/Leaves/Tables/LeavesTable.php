<?php

namespace App\Filament\Resources\Leaves\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\ActionGroup;
use Filament\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Table;
use Filament\Actions\{EditAction, DeleteAction, ViewAction, DeleteBulkAction, ForceDeleteBulkAction, RestoreBulkAction};
use Illuminate\Support\Facades\Auth;
use function App\Helpers\getUserAuditColumn;

/**
 * LeavesTable: Restricts actions per user permission ("manage_own_leaves") and
 * customizes per-record visibility for edit/update/delete/approve/reject.
 */
class LeavesTable
{
    public static function configure(Table $table): Table
    {
        $user = Auth::user();

        // Determine if current user can manage all, or only their own
        $canManageAll = $user && (
            $user->hasRole('super_admin')
            || $user->can('manage_leaves')
            || $user->can('edit_leaves') // manage_all includes edit_all
            || $user->can('delete_leaves')
            || $user->can('view_any_leaves')
        );
        $canManageOwn = $user && (
            $user->can('manage_own_leaves') // manage/edit/delete ONLY their own leaves
        );

        return $table
            ->columns([
                TextColumn::make('user.name')
                    ->label('User')
                    ->searchable()
                    ->visible($canManageAll),

                // Only show user column to users managing all (HR/admin)
                // For self-management or "own", user sees only their own requests, so ignore column

                TextColumn::make('reason')
                    ->label('Reason')
                    ->wrap()
                    ->toggleable(),

                TextColumn::make('start_date')
                    ->date()
                    ->label('Start Date')
                    ->sortable(),

                TextColumn::make('end_date')
                    ->date()
                    ->label('End Date')
                    ->sortable(),

                TextColumn::make('type')
                    ->label('Type')
                    ->searchable(),

                BadgeColumn::make('status')
                    ->label('Status')
                    ->formatStateUsing(function ($state) {
                        return match ($state) {
                            'pending' => 'Pending',
                            'approved' => 'Approved',
                            'rejected' => 'Rejected',
                            default => $state,
                        };
                    })
                    ->colors([
                        'warning' => 'pending',
                        'success' => 'approved',
                        'danger' => 'rejected',
                        'gray' => null,
                    ])
                    ->icon(
                        fn($state) =>
                        match ($state) {
                            'approved' => 'heroicon-o-check-circle',
                            'rejected' => 'heroicon-o-x-circle',
                            'pending' => 'heroicon-o-clock',
                            default => null,
                        }
                    ),

                TextColumn::make('status_comment')
                    ->label('Approval/Reject Comment')
                    ->wrap()
                    ->toggleable()
                    ->formatStateUsing(
                        fn($state, $record) => ($record->status === 'approved'
                            ? '<span style="color:green" title="Approved"><svg xmlns="http://www.w3.org/2000/svg" style="height:1em;width:1em;vertical-align:-0.125em;" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg></span> '
                            : ($record->status === 'rejected'
                                ? '<span style="color:red" title="Rejected"><svg xmlns="http://www.w3.org/2000/svg" style="height:1em;width:1em;vertical-align:-0.125em;" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></span> '
                                : '')
                        ) . e($state)
                    )
                    ->html(),

                TextColumn::make('created_at')
                    ->dateTime('M j, Y')
                    ->label('Applied On')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: false),

                TextColumn::make('updated_at')
                    ->dateTime('M j, Y')
                    ->label('Updated At')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('deleted_at')
                    ->dateTime('M j, Y')
                    ->label('Deleted At')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                getUserAuditColumn('creator', 'Created By'),
                getUserAuditColumn('updater', 'Updated By'),
                getUserAuditColumn('deleter', 'Deleted By'),
            ])
            ->filters([
            ])
            ->recordActions([
                // Approve permission: manager/admin only (can update others)
                Action::make('approve')
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->visible(function ($record) use ($user, $canManageAll) {
                        // Only show to those with update permission & on pending
                        if (!$user || $record->status !== 'pending') return false;

                        // Allow if manage_all, OR manage_own & this is their record
                        if ($canManageAll) return true;
                        if ($user->can('manage_own_leaves') && $record->user_id == $user->id)
                            return true;
                        return false;
                    })
                    ->modalHeading('Approve Leave Request')
                    ->modalDescription('Please confirm approval and optionally add a comment.')
                    ->form([
                        \Filament\Forms\Components\Textarea::make('status_comment')
                            ->placeholder('Enter comment (optional)'),
                    ])
                    ->action(function (array $data, $record) {
                        $record->status = 'approved';
                        $record->status_comment = $data['status_comment'] ?? null;
                        $record->save();
                    })
                    ->modalSubmitActionLabel('Approve')
                    ->label(''),

                // Reject permission: manager/admin only (can update others)
                Action::make('reject')
                    ->icon('heroicon-o-x-mark')
                    ->color('danger')
                    ->visible(function ($record) use ($user, $canManageAll) {
                        if (!$user || $record->status !== 'pending') return false;
                        if ($canManageAll) return true;
                        if ($user->can('manage_own_leaves') && $record->user_id == $user->id)
                            return true;
                        return false;
                    })
                    ->modalHeading('Reject Leave Request')
                    ->modalDescription('Please provide a reason for rejection.')
                    ->form([
                        \Filament\Forms\Components\Textarea::make('status_comment')
                            ->required()
                            ->placeholder('Enter reason for rejection'),
                    ])
                    ->action(function (array $data, $record) {
                        $record->status = 'rejected';
                        $record->status_comment = $data['status_comment'];
                        $record->save();
                    })
                    ->modalSubmitActionLabel('Reject')
                    ->label(''),

                ActionGroup::make([
                    ViewAction::make()
                        ->visible(function ($record) use ($user, $canManageAll, $canManageOwn) {
                            // View always allowed if can manage all, or for own record if they have any access
                            if (!$user) return false;
                            if ($canManageAll) return true;
                            if ($canManageOwn && $record->user_id == $user->id) return true;
                            // fallback: anyone viewing their own
                            if ($user->id == $record->user_id) return true;
                            return false;
                        }),

                    EditAction::make()
                        ->visible(function ($record) use ($user, $canManageAll, $canManageOwn) {
                            if (!$user) return false;
                            if ($canManageAll) return true;
                            if ($canManageOwn && $record->user_id == $user->id) return true;
                            // fallback: allow self-edit if manage_own
                            if ($user->can('manage_own_leaves') && $record->user_id == $user->id) return true;
                            return false;
                        }),

                    DeleteAction::make()
                        ->visible(function ($record) use ($user, $canManageAll, $canManageOwn) {
                            if (!$user) return false;
                            if ($canManageAll) return true;
                            if ($canManageOwn && $record->user_id == $user->id) return true;
                            if ($user->can('manage_own_leaves') && $record->user_id == $user->id) return true;
                            return false;
                        }),
                ]),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->visible($canManageAll || $canManageOwn),
                    ForceDeleteBulkAction::make()
                        ->visible($canManageAll),
                    RestoreBulkAction::make()
                        ->visible($canManageAll),
                ]),
            ])
            ->recordUrl(null);
    }
}
