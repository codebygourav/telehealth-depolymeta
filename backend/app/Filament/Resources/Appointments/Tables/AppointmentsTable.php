<?php

namespace App\Filament\Resources\Appointments\Tables;

use App\Filament\Resources\Appointments\AppointmentResource;
use Filament\Facades\Filament;
use Filament\Tables\Table;
use Filament\Tables\Columns\{TextColumn, BadgeColumn};
use Filament\Actions\{ActionGroup, BulkActionGroup, DeleteBulkAction, ForceDeleteBulkAction, RestoreBulkAction, ViewAction, DeleteAction};
use Filament\Tables\Filters\{Filter, SelectFilter};
use function Laravel\Prompts\table;
use function App\Helpers\getUserAuditColumn;
use App\Enums\AppointmentStatus;
use App\Enums\PaymentStatus;

class AppointmentsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(function ($query) {
                // Eager load relationships to prevent N+1 queries
                return $query->with([
                    'patient:id,user_id,first_name,last_name',
                    'doctor:id,user_id,first_name,last_name',
                    'doctor.user:id,name,email',
                    'payment:id,appointment_id,status,razorpay_payment_id',
                    'availability:id,doctor_id,opd_type,consultation_type',
                    'doctor.replacements' => function ($q) {
                        $q->where('is_active', true)
                            ->with(['replacementDoctor:id,first_name,last_name']);
                    }
                ]);
            })
            ->columns([

                TextColumn::make('id')
                    ->label('Appoinment ID')
                    ->sortable(),

                TextColumn::make('payment.razorpay_payment_id')
                    ->label('Razorpay ID')
                    ->searchable()
                    ->toggleable(),

                BadgeColumn::make('payment.status')
                    ->label('Payment Status')
                    ->searchable()
                    ->formatStateUsing(function ($state) {
                        if (!$state) return 'Unpaid';
                        
                        $statusEnum = $state instanceof PaymentStatus
                            ? $state
                            : PaymentStatus::tryFrom($state);

                        return $statusEnum
                            ? $statusEnum->label()
                            : (is_string($state) ? ucfirst($state) : 'Unpaid');
                    })
                    ->color(function ($state) {
                        if (!$state) return 'danger';
                        
                        $statusValue = $state instanceof PaymentStatus
                            ? $state->value
                            : (is_object($state) ? null : $state);

                        return match ($statusValue) {
                            PaymentStatus::PENDING->value  => 'warning',
                            PaymentStatus::PAID->value     => 'success',
                            PaymentStatus::FAILED->value   => 'danger',
                            PaymentStatus::REFUNDED->value => 'secondary',
                            default => 'danger',
                        };
                    })
                    ->sortable(),

                TextColumn::make('appointment_date')
                    ->label('Date')
                    ->date()
                    ->sortable(),

                TextColumn::make('appointment_time')
                    ->label('Time')
                    ->time('H:i')
                    ->sortable(),

                TextColumn::make('patient.first_name')
                    ->label('Patient')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('doctor.user.name')
                    ->label('Doctor')
                    ->searchable()
                    ->sortable()
                    ->description(function ($record) {
                        // Use pre-loaded relationship instead of querying
                        $replacement = $record->doctor->replacements
                            ->firstWhere(function ($rep) use ($record) {
                                return (!$rep->start_date || $rep->start_date <= $record->appointment_date) &&
                                    (!$rep->end_date || $rep->end_date >= $record->appointment_date);
                            });

                        if ($replacement && $replacement->replacementDoctor) {
                            return 'Replaced by: Dr. ' . $replacement->replacementDoctor->first_name . ' ' . $replacement->replacementDoctor->last_name;
                        }
                        return null;
                    })
                    ->badge(function ($record) {
                        if ($record->hasActiveReplacement()) {
                            return 'Replaced';
                        }
                        return null;
                    })
                    ->color(fn($record) => $record->hasActiveReplacement() ? 'warning' : null),


                BadgeColumn::make('status')
                    ->label('Status')
                    ->formatStateUsing(function ($state) {
                        $statusEnum = $state instanceof AppointmentStatus
                            ? $state
                            : AppointmentStatus::tryFrom($state);

                        return $statusEnum
                            ? $statusEnum->label()
                            : (is_string($state) ? ucfirst($state) : '');
                    })
                    ->color(function ($state) {
                        $statusValue = $state instanceof AppointmentStatus
                            ? $state->value
                            : (is_object($state) ? null : $state);

                        return match ($statusValue) {
                            AppointmentStatus::PENDING->value      => 'warning',
                            AppointmentStatus::CONFIRMED->value    => 'info',
                            AppointmentStatus::COMPLETED->value    => 'success',
                            AppointmentStatus::RESCHEDULED->value  => 'primary',
                            AppointmentStatus::CANCELLED->value    => 'danger',
                            AppointmentStatus::FAILED->value       => 'danger',
                            AppointmentStatus::NO_SHOW->value      => 'danger',
                            default => null,
                        };
                    })
                    ->sortable(),
                TextColumn::make('consultation_type')
                    ->label('Appointment Mode'),
                getUserAuditColumn('creator', 'Created By'),
                getUserAuditColumn('updater', 'Updated By'),
                getUserAuditColumn('deleter', 'Deleted By'),
            ])

            ->filters([
                // --------------------------
                // 1. DATE RANGE FILTER
                // --------------------------
                Filter::make('appointment_date')
                    ->form([
                        \Filament\Forms\Components\DatePicker::make('date')->label('appointment_date'),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when($data['date'], fn($q) => $q->whereDate('appointment_date', '=', $data['date']));
                    }),

                // --------------------------
                // 2. TIME RANGE FILTER
                // --------------------------
                \Filament\Tables\Filters\SelectFilter::make('appointment_time')
                    ->label('Appointment Time')
                    ->options(
                        \App\Models\Appointment::query()
                            ->select('appointment_time')
                            ->whereNotNull('appointment_time')
                            ->distinct()
                            ->orderBy('appointment_time')
                            ->pluck('appointment_time', 'appointment_time')
                            ->filter(fn($value) => !is_null($value))
                            ->toArray()
                    ),

                // --------------------------
                // 3. DOCTOR FILTER
                // --------------------------
                SelectFilter::make('doctor_id')
                    ->label('Doctor')
                    ->relationship('doctor.user', 'name', function ($query) {
                        return $query->whereNotNull('name');
                    }) // doctor → user.name
                    ->searchable()
                    ->preload(),

                // --------------------------
                // 4. STATUS FILTER
                // --------------------------
                SelectFilter::make('status')
                    ->label('Status')
                    ->options(
                        collect(AppointmentStatus::cases())
                            ->mapWithKeys(fn($status) => [$status->value => $status->label()])
                            ->toArray()
                    ),

                // --------------------------
                // 5. APPOINTMENT MODE FILTER (In-Person or Video)
                // --------------------------
                SelectFilter::make('consultation_type')
                    ->label('Appointment Mode')
                    ->options([
                        'in-person' => 'In-Person',
                        'video'     => 'Video',
                    ]),

                // --------------------------
                // 6. OPD TYPE FILTER (General or Private) - Only for In-Person appointments
                // --------------------------
                SelectFilter::make('opd_type')
                    ->label('OPD Type')
                    ->placeholder('All OPD Types')
                    ->options([
                        'general' => 'General OPD',
                        'private' => 'Private OPD',
                    ])
                    ->query(function ($query, $data) {
                        $opdType = $data['value'] ?? null;
                        if (!$opdType) {
                            return $query;
                        }
                        // Filter appointments that have availability with the specified OPD type
                        // and are in-person (since OPD type only applies to in-person)
                        return $query->where('consultation_type', 'in-person')
                            ->whereHas('availability', function ($q) use ($opdType) {
                                $q->where('opd_type', $opdType);
                            });
                    })
                    ->visible(function ($livewire) {
                        // Only show OPD Type filter when Appointment Mode is NOT 'video'
                        $filterState = $livewire->getTableFilterState('consultation_type');
                        $consultationType = $filterState['value'] ?? null;

                        // Hide if video is selected, show otherwise
                        return $consultationType !== 'video';
                    }),
            ])


            ->extraAttributes([
                'class' => 'custom-pagination',
            ])

            ->recordActions([
                ActionGroup::make([
                    ViewAction::make(),
                    \Filament\Actions\Action::make('replace_doctor')
                        ->label('Replace Doctor')
                        ->icon('heroicon-o-arrow-path')
                        ->color('warning')
                        ->form([
                            \Filament\Forms\Components\Select::make('replacement_doctor_id')
                                ->label('Replacement Doctor')
                                ->relationship('replacementDoctor', 'first_name', function ($query, $record) {
                                    $query->where('id', '!=', $record->doctor_id)
                                        ->where('status', 'active');
                                })
                                ->getOptionLabelFromRecordUsing(function ($record) {
                                    $firstName = $record->first_name ?? '';
                                    $lastName = $record->last_name ?? '';
                                    $name = trim("Dr. {$firstName} {$lastName}");
                                    return $name ?: 'Unknown Doctor';
                                })
                                ->searchable(['first_name', 'last_name'])
                                ->required(),
                            \Filament\Forms\Components\TextInput::make('replacement_room')
                                ->label('Replacement Doctor Room')
                                ->placeholder('e.g., Room 101')
                                ->required()
                                ->helperText('Room where replacement doctor will consult')
                                ->maxLength(255),
                            \Filament\Forms\Components\Select::make('reason')
                                ->label('Reason')
                                ->options([
                                    'leave' => 'Doctor on Leave',
                                    'unavailable' => 'Doctor Unavailable',
                                    'emergency' => 'Emergency',
                                    'sick' => 'Doctor Sick',
                                    'other' => 'Other',
                                ])
                                ->default('unavailable'),
                            \Filament\Forms\Components\Textarea::make('notes')
                                ->label('Notes')
                                ->rows(3),
                        ])
                        ->action(function ($record, array $data) {
                            \Illuminate\Support\Facades\DB::transaction(function () use ($record, $data) {
                                // Create replacement record
                                $replacement = \App\Models\DoctorReplacement::create([
                                    'id' => (string) \Illuminate\Support\Str::uuid(),
                                    'original_doctor_id' => $record->doctor_id,
                                    'replacement_doctor_id' => $data['replacement_doctor_id'],
                                    'replacement_type' => 'single',
                                    'start_date' => $record->appointment_date,
                                    'end_date' => $record->appointment_date,
                                    'reason' => $data['reason'] ?? 'unavailable',
                                    'notes' => $data['notes'] ?? null,
                                    'is_active' => true,
                                    'replaced_by' => \Illuminate\Support\Facades\Auth::id(),
                                ]);

                                // Find or create availability
                                $availability = self::findOrCreateAvailability(
                                    $data['replacement_doctor_id'],
                                    $record->appointment_date,
                                    $record->appointment_time,
                                    $record->consultation_type,
                                    $data['replacement_room']
                                );

                                // Update appointment - set doctor_id to replacement (for queries) AND replaced_by_id (for tracking)
                                $record->update([
                                    'doctor_id' => $data['replacement_doctor_id'],
                                    'replaced_by_id' => $data['replacement_doctor_id'],
                                    'availability_id' => $availability->id,
                                ]);
                            });

                            \Filament\Notifications\Notification::make()
                                ->title('Doctor Replaced')
                                ->body('Original doctor preserved. Appointment assigned to replacement doctor.')
                                ->success()
                                ->send();
                        })
                        ->visible(fn($record) => !$record->hasActiveReplacement() && !in_array($record->status, ['cancelled', 'completed'])),
                    DeleteAction::make()
                        ->requiresConfirmation(),
                ]),
            ])

            ->toolbarActions([
                BulkActionGroup::make([
                    \Filament\Actions\BulkAction::make('replace_doctors')
                        ->label('Replace Doctors')
                        ->icon('heroicon-o-arrow-path')
                        ->color('warning')
                        ->form([
                            \Filament\Forms\Components\Select::make('replacement_doctor_id')
                                ->label('Replacement Doctor')
                                ->relationship('replacementDoctor', 'first_name', function ($query) {
                                    $query->where('status', 'active');
                                })
                                ->getOptionLabelFromRecordUsing(function ($record) {
                                    $firstName = $record->first_name ?? '';
                                    $lastName = $record->last_name ?? '';
                                    $name = trim("Dr. {$firstName} {$lastName}");
                                    return $name ?: 'Unknown Doctor';
                                })
                                ->searchable(['first_name', 'last_name'])
                                ->required(),
                            \Filament\Forms\Components\TextInput::make('replacement_room')
                                ->label('Replacement Doctor Room')
                                ->placeholder('e.g., Room 101')
                                ->required()
                                ->helperText('Room where replacement doctor will consult')
                                ->maxLength(255),
                            \Filament\Forms\Components\Select::make('reason')
                                ->label('Reason')
                                ->options([
                                    'leave' => 'Doctor on Leave',
                                    'unavailable' => 'Doctor Unavailable',
                                    'emergency' => 'Emergency',
                                    'sick' => 'Doctor Sick',
                                    'other' => 'Other',
                                ])
                                ->default('unavailable'),
                            \Filament\Forms\Components\Textarea::make('notes')
                                ->label('Notes')
                                ->rows(3),
                        ])
                        ->action(function ($records, array $data) {
                            \Illuminate\Support\Facades\DB::transaction(function () use ($records, $data) {
                                $appointments = $records->filter(fn($r) => !$r->hasActiveReplacement() && !in_array($r->status, ['cancelled', 'completed']));

                                if ($appointments->isEmpty()) {
                                    \Filament\Notifications\Notification::make()
                                        ->title('No Valid Appointments')
                                        ->body('No appointments can be replaced.')
                                        ->warning()
                                        ->send();
                                    return;
                                }

                                // Get original doctor from first appointment
                                $originalDoctorId = $appointments->first()->doctor_id;
                                $dates = $appointments->pluck('appointment_date')->unique()->sort();

                                // Create replacement record
                                $replacement = \App\Models\DoctorReplacement::create([
                                    'id' => (string) \Illuminate\Support\Str::uuid(),
                                    'original_doctor_id' => $originalDoctorId,
                                    'replacement_doctor_id' => $data['replacement_doctor_id'],
                                    'replacement_type' => 'selected',
                                    'start_date' => $dates->first(),
                                    'end_date' => $dates->last(),
                                    'reason' => $data['reason'] ?? 'unavailable',
                                    'notes' => $data['notes'] ?? null,
                                    'is_active' => true,
                                    'replaced_by' => \Illuminate\Support\Facades\Auth::id(),
                                ]);

                                // Replace each appointment - set doctor_id to replacement (for queries) AND replaced_by_id (for tracking)
                                foreach ($appointments as $appointment) {
                                    $availability = self::findOrCreateAvailability(
                                        $data['replacement_doctor_id'],
                                        $appointment->appointment_date,
                                        $appointment->appointment_time,
                                        $appointment->consultation_type,
                                        $data['replacement_room']
                                    );

                                    $appointment->update([
                                        'doctor_id' => $data['replacement_doctor_id'],
                                        'replaced_by_id' => $data['replacement_doctor_id'],
                                        'availability_id' => $availability->id,
                                    ]);
                                }
                            });

                            \Filament\Notifications\Notification::make()
                                ->title('Doctors Replaced')
                                ->body(count($records) . ' appointment(s) have been assigned to the replacement doctor.')
                                ->success()
                                ->send();
                        })
                        ->deselectRecordsAfterCompletion()
                        ->visible(fn() => AppointmentResource::canEdit(null)),
                    DeleteBulkAction::make()
                        ->requiresConfirmation()
                        ->visible(fn() => AppointmentResource::canDelete(null)),
                    RestoreBulkAction::make()
                        ->visible(fn() => AppointmentResource::canEdit(null)),
                    ForceDeleteBulkAction::make()
                        ->requiresConfirmation()
                        ->visible(fn() => AppointmentResource::canDelete(null)),
                ]),
            ]);
    }

    /**
     * Find or create availability for replacement doctor
     */
    protected static function findOrCreateAvailability(
        string $doctorId,
        string $date,
        string $time,
        string $consultationType,
        ?string $room = null
    ): \App\Models\DoctorAvailability {
        // Try to find existing availability
        $availability = \App\Models\DoctorAvailability::where('doctor_id', $doctorId)
            ->whereDate('date', $date)
            ->whereTime('start_time', '<=', $time)
            ->whereTime('end_time', '>=', $time)
            ->where('consultation_type', $consultationType)
            ->where('is_available', true)
            ->first();

        if ($availability) {
            // Update room if provided
            if ($room && $availability->doctor_room !== $room) {
                $availability->update(['doctor_room' => $room]);
            }
            return $availability;
        }

        // Create new availability
        $startTime = \Carbon\Carbon::parse($time);
        $endTime = $startTime->copy()->addHour(); // Default 1 hour slot

        return \App\Models\DoctorAvailability::create([
            'id' => (string) \Illuminate\Support\Str::uuid(),
            'doctor_id' => $doctorId,
            'date' => $date,
            'day_of_week' => strtolower(\Carbon\Carbon::parse($date)->format('l')),
            'start_time' => $startTime->format('H:i:00'),
            'end_time' => $endTime->format('H:i:00'),
            'consultation_type' => $consultationType,
            'capacity' => 1,
            'doctor_room' => $room,
            'is_available' => true,
            'is_recurring' => false,
        ]);
    }
}
