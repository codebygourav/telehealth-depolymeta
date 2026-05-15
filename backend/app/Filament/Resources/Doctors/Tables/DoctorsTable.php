<?php

namespace App\Filament\Resources\Doctors\Tables;

use App\Enums\GenderOption;
use App\Filament\Resources\Doctors\DoctorResource;
use App\Models\Department;
use App\Models\DepartmentDoctor;
use Filament\Actions\{Action, ActionGroup, BulkAction, BulkActionGroup, DeleteBulkAction, ForceDeleteBulkAction, RestoreBulkAction, ViewAction, DeleteAction, EditAction};
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

use function App\Helpers\getUserAuditColumn;

class DoctorsTable
{
    public static function configure(Table $table): Table
    {
        // Only deny access for unauthenticated users. Any logged-in user
        // will be able to view the doctors table. Actions (edit/delete)
        // are still gated by resource permission checks below.
        if (! auth()->check()) {
            return $table
                ->columns([])
                ->filters([])
                ->recordActions([])
                ->toolbarActions([])
                ->query(fn($query) => $query->whereRaw('1 = 0'))
                ->emptyStateHeading('Access Denied')
                ->emptyStateDescription('You do not have permission to view doctors.')
                ->emptyStateIcon('heroicon-o-lock-closed');
        }

        // Add a custom header view with the "note" above the table, under the header bar (where the search/filter is)

        return $table
            ->heading(
                "Note: Use the Department filter to update the order for Doctor within that department. Click the Status badge in a doctor's row to update the status directly."
            )
            ->modifyQueryUsing(function ($query) {
                // Eager load relationships to prevent N+1 queries
                return $query->with([
                    'user:id,name,email,phone', // avatar is accessed via InteractsWithModuleDocuments trait
                    'replacements' => function ($q) {
                        $q->where('is_active', true)
                            ->where(function ($q) {
                                $q->whereNull('start_date')
                                    ->orWhere('start_date', '<=', now()->format('Y-m-d'));
                            })
                            ->where(function ($q) {
                                $q->whereNull('end_date')
                                    ->orWhere('end_date', '>=', now()->format('Y-m-d'));
                            })
                            ->with(['replacementDoctor:id,first_name,last_name']);
                    },
                ]);
            })
            ->columns([
                ImageColumn::make('avatar')
                    ->label('Photo')
                    ->circular()
                    ->disk('public')
                    ->getStateUsing(function ($record) {
                        // Always use the correct avatar path with storage URL helper; fallback to default image.
                        $avatar = $record->avatar ?? $record->user?->avatar;
                        if ($avatar) {
                            // Use helper if present, else Storage facade (should match your codebase conventions)
                            return function_exists('storage_url') ? storage_url($avatar) : \Illuminate\Support\Facades\Storage::disk('public')->url($avatar);
                        }
                        return asset('images/user-avatar.png');
                    }),



                TextColumn::make('doctor_code')
                    ->label('ID')
                    ->sortable()
                    ->searchable()
                    ->toggleable()
                    ->color('primary-950'),

                TextColumn::make('user.name')
                    ->label('Name')
                    ->sortable()
                    ->searchable(),


                TextColumn::make('user.phone')
                    ->label('Phone')
                    ->searchable()
                    ->toggleable(),



                TextColumn::make('replaced_by_doctor')
                    ->label('Replaced By')
                    ->getStateUsing(function ($record) {
                        // Use pre-loaded relationship instead of querying
                        $activeReplacement = $record->replacements->first();

                        if ($activeReplacement && $activeReplacement->replacementDoctor) {
                            return "Dr. {$activeReplacement->replacementDoctor->first_name} {$activeReplacement->replacementDoctor->last_name}";
                        }

                        return '—';
                    })
                    ->badge()
                    ->color(function ($record) {
                        // Use pre-loaded relationship
                        return $record->replacements->isNotEmpty() ? 'warning' : 'gray';
                    })
                    ->tooltip(function ($record) {
                        // Use pre-loaded relationship
                        $activeReplacement = $record->replacements->first();

                        if ($activeReplacement && $activeReplacement->replacementDoctor) {
                            $dateRange = '';
                            if ($activeReplacement->start_date && $activeReplacement->end_date) {
                                $dateRange = " ({$activeReplacement->start_date->format('M d')} - {$activeReplacement->end_date->format('M d, Y')})";
                            } elseif ($activeReplacement->start_date) {
                                $dateRange = " (from {$activeReplacement->start_date->format('M d, Y')})";
                            }

                            return "Replaced by Dr. {$activeReplacement->replacementDoctor->first_name} {$activeReplacement->replacementDoctor->last_name}{$dateRange}";
                        }

                        return null;
                    })
                    ->toggleable(),

                // Add a note into the Status column label clarifying it's updatable
                BadgeColumn::make('status')
                    ->label('Status')
                    ->description('Click to update status')
                    ->tooltip('Directly update doctor status')
                    ->icon('heroicon-m-pencil-square')
                    ->iconPosition('after')
                    ->getStateUsing(fn($record) => $record->status instanceof \App\Enums\DoctorStatus ? $record->status->value : $record->status)
                    ->colors([
                        'warning' => \App\Enums\DoctorStatus::PENDING_VERIFICATION->value,
                        'danger' => \App\Enums\DoctorStatus::REJECTED->value,
                        'secondary' => \App\Enums\DoctorStatus::SUSPENDED->value,
                        'primary' => \App\Enums\DoctorStatus::ACTIVE->value,
                    ])
                    ->formatStateUsing(fn($state) => \App\Enums\DoctorStatus::values()[$state] ?? $state)
                    ->sortable()
                    ->extraAttributes(['class' => 'cursor-pointer hover:opacity-80 transition-opacity'])
                    ->action(
                        Action::make('editStatus')
                            ->label('Update Status')
                            ->icon('heroicon-o-pencil')
                            ->modalHeading('Update Doctor Status')
                            ->form([
                                \Filament\Forms\Components\Select::make('status')
                                    ->label('Status')
                                    ->options(\App\Enums\DoctorStatus::values())
                                    ->required(),
                            ])
                            ->mountUsing(function ($form, $record) {
                                $form->fill(['status' => $record->status instanceof \BackedEnum ? $record->status->value : $record->status]);
                            })
                            ->action(function (array $data, $record) {
                                $record->status = $data['status'];
                                $record->save();
                            })
                            ->modalSubmitActionLabel('Save')
                    ),

                getUserAuditColumn('creator', 'Created By'),
                getUserAuditColumn('updater', 'Updated By'),
                getUserAuditColumn('deleter', 'Deleted By'),

                TextColumn::make('department_order')
                    ->label('Order')
                    ->description('Click to edit order')
                    ->tooltip('Edit department display order')
                    ->icon('heroicon-m-pencil-square')
                    ->iconPosition('after')
                    ->sortable()
                    ->visible(fn($livewire) => filled($livewire->getTableFilterState('department')['value'] ?? null))
                    ->getStateUsing(function ($record, $livewire) {
                        $slug = $livewire->getTableFilterState('department')['value'] ?? null;
                        if (! $slug) {
                            return null;
                        }
                        $departmentId = Department::where('slug', $slug)->value('id');

                        return $record->departments()->where('departments.id', $departmentId)->first()?->pivot?->order;
                    })
                    ->extraAttributes(['class' => 'cursor-pointer hover:opacity-80 transition-opacity'])
                    ->action(
                        Action::make('editOrder')
                            ->label('Edit Order')
                            ->icon('heroicon-o-pencil')
                            ->modalHeading('Edit Doctor Order')
                            ->form([TextInput::make('order')->label('Order')->numeric()->required()])
                            ->mountUsing(function ($form, $record, $livewire) {
                                $slug = $livewire->getTableFilterState('department')['value'] ?? null;
                                if (! $slug) {
                                    return;
                                }
                                $departmentId = Department::where('slug', $slug)->value('id');
                                if (! $departmentId) {
                                    return;
                                }
                                $order = $record->departments()->where('departments.id', $departmentId)->first()?->pivot?->order;
                                $form->fill(['order' => $order]);
                            })
                            ->action(function (array $data, $record, $livewire) {
                                $slug = $livewire->getTableFilterState('department')['value'] ?? null;
                                if (! $slug) {
                                    return;
                                }
                                $departmentId = Department::where('slug', $slug)->value('id');
                                if (! $departmentId) {
                                    return;
                                }
                                DepartmentDoctor::updateOrCreate(
                                    ['doctor_id' => $record->id, 'department_id' => $departmentId],
                                    ['order' => (int) $data['order']]
                                );
                                $livewire->dispatch('$refresh');
                            })
                            ->modalSubmitActionLabel('Save')
                    ),
            ])
            ->filters([
                TrashedFilter::make(),
                SelectFilter::make('department')
                    ->label('Department')
                    ->options(fn() => Department::pluck('name', 'slug'))
                    ->query(function ($query, $data) {
                        $slug = $data['value'] ?? null;
                        if (! $slug) {
                            return $query;
                        }
                        $departmentId = Department::where('slug', $slug)->value('id');

                        return $query->whereHas('departments', function ($q) use ($departmentId) {
                            $q->where('departments.id', $departmentId);
                        });
                    }),
                TernaryFilter::make('has_opd_schedule')
                    ->label('Filter to show those doctors only who are having OPD schedule')
                    ->placeholder('All doctors')
                    ->trueLabel('Doctors with OPD schedule')
                    ->falseLabel('Doctors without OPD schedule')
                    ->queries(
                        true: fn($query) => $query->whereHas('availabilities', function ($q) {
                            $q->whereNotNull('opd_type')
                                ->where('is_available', true);
                        }),
                        false: fn($query) => $query->whereDoesntHave('availabilities', function ($q) {
                            $q->whereNotNull('opd_type')
                                ->where('is_available', true);
                        }),
                        blank: fn($query) => $query,
                    ),
            ])
            ->reorderable('department_order', function ($livewire) {
                return filled($livewire->getTableFilterState('department')['value'] ?? null);
            })
            ->bulkActions([
                BulkActionGroup::make([
                    BulkAction::make('editStatus')
                        ->label('Update Status')
                        ->icon('heroicon-o-pencil')
                        ->form([
                            \Filament\Forms\Components\Select::make('status')
                                ->label('Status')
                                ->options(\App\Enums\DoctorStatus::values())
                                ->required(),
                        ])
                        ->action(function (\Illuminate\Database\Eloquent\Collection $records, array $data): void {
                            $records->each(function ($record) use ($data) {
                                $record->status = $data['status'];
                                $record->save();
                            });

                            \Filament\Notifications\Notification::make()
                                ->title('Status Updated')
                                ->body('The status of selected doctors has been updated.')
                                ->success()
                                ->send();
                        })
                        ->deselectRecordsAfterCompletion(),
                    DeleteBulkAction::make()
                        ->visible(fn() => DoctorResource::canDelete(null)), // bulk actions usually check overall permission
                    ForceDeleteBulkAction::make()
                        ->visible(fn() => DoctorResource::canDelete(null)),
                    RestoreBulkAction::make()
                        ->visible(fn() => DoctorResource::canEdit(null)), // example, adjust based on your logic
                ]),
            ])
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make()
                        ->icon('heroicon-o-eye')
                        ->visible(fn($record) => DoctorResource::canViewAny()),

                    EditAction::make()
                        ->visible(fn($record) => DoctorResource::canEdit($record)),

                    DeleteAction::make()
                        ->visible(fn($record) => DoctorResource::canDelete($record)),
                ]),
            ])
            ->defaultSort('doctor_code')
            ->extraAttributes([
                'class' => 'custom-pagination',
            ])
            ->recordUrl(null);
    }
}
