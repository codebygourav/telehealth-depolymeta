<?php

namespace App\Filament\Resources\Appointments\Tables;

use App\Filament\Resources\Appointments\AppointmentResource;
use Filament\Facades\Filament;
use Filament\Schemas\Schema;
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
            ->searchable(false)
            ->modifyQueryUsing(function ($query) {
                // Eager load relationships to prevent N+1 queries
                return $query->with([
                    'patient:id,user_id,first_name,last_name,existing_patient_id',
                    'doctor:id,user_id,first_name,last_name',
                    'doctor.user:id,name,email',
                    'payment:id,appointment_id,status,razorpay_payment_id',
                    'paymentWaiver:id,name',
                    'availability:id,doctor_id,opd_type,consultation_type',
                    'videoConsultation:id,appointment_id,room_url,host_url,participate_url,room_id,status,started_at,ended_at',
                    'doctor.replacements' => function ($q) {
                        $q->where('is_active', true)
                            ->with(['replacementDoctor:id,first_name,last_name']);
                    }
                ]);
            })
            ->defaultSort('created_at', 'desc')
            ->columns([

                TextColumn::make('created_at')
                    ->label('Booking Created On')
                    ->html()
                    ->formatStateUsing(function ($record) {
                        if (!$record || !$record->created_at) return '-';
                        $carbon = \Carbon\Carbon::parse($record->created_at);
                        $date = $carbon->format('M d, Y');
                        $time = $carbon->format('h:i A');
                        $isTodayBadge = '';
                        if ($carbon->isToday()) {
                            $isTodayBadge = "<span class='inline-flex items-center px-1.5 py-0.5 rounded text-[9px] font-black bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300 border border-emerald-300 dark:border-emerald-800 uppercase tracking-wider animate-pulse ml-1.5'>New Today</span>";
                        }
                        return "<div class='flex flex-col gap-1'>
                                    <div class='flex items-center'>
                                        <span class='text-sm font-semibold text-gray-900 dark:text-white'>{$date}</span>
                                        {$isTodayBadge}
                                    </div>
                                    <div class='text-xs text-gray-500'>{$time}</div>
                                </div>";
                    })
                    ->sortable()
                    ->toggleable(),

                BadgeColumn::make('payment_display_status')
                    ->label('Payment Status')
                    ->getStateUsing(function ($record) {
                        if (($record->booking_source ?? null) === 'admin' && ($record->admin_payment_type ?? null) === 'without_payment') {
                            return 'admin_without_payment';
                        }

                        return $record->payment?->status;
                    })
                    ->formatStateUsing(function ($state) {
                        if ($state === 'admin_without_payment') {
                            return 'Admin No Payment';
                        }

                        if (!$state) return 'Unpaid';

                        $statusEnum = $state instanceof PaymentStatus
                            ? $state
                            : PaymentStatus::tryFrom($state);

                        return $statusEnum
                            ? $statusEnum->label()
                            : (is_string($state) ? ucfirst($state) : 'Unpaid');
                    })
                    ->color(function ($state) {
                        if ($state === 'admin_without_payment') {
                            return 'info';
                        }

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
                    }),

                BadgeColumn::make('booking_source')
                    ->label('Booking Source')
                    ->getStateUsing(function ($record) {
                        if (($record->booking_source ?? null) === 'admin') {
                            return ($record->admin_payment_type ?? null) === 'without_payment'
                                ? 'Admin - No Payment'
                                : 'Admin - With Payment';
                        }

                        return is_string($record->booking_source ?? null)
                            ? str($record->booking_source)->replace('_', ' ')->title()->toString()
                            : 'Patient';
                    })
                    ->color(fn ($state) => str_starts_with((string) $state, 'Admin') ? 'primary' : 'gray')
                    ->toggleable(),

                TextColumn::make('appointment_date')
                    ->label('OPD Visit Date')
                    ->html()
                    ->formatStateUsing(function ($record) {
                        if (!$record->appointment_date) {
                            return '-';
                        }
                        $date = \Carbon\Carbon::parse($record->appointment_date)->format('M d, Y');
                        $startTime = $record->appointment_time
                            ? \Carbon\Carbon::parse($record->appointment_time)->format('h:i A')
                            : '';
                        $endTime = $record->appointment_end_time
                            ? \Carbon\Carbon::parse($record->appointment_end_time)->format('h:i A')
                            : '';
                        $timeRange = trim("{$startTime} - {$endTime}", ' -');

                        return "<div class='flex flex-col gap-1.5 py-1'>
                                    <span class='inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-bold bg-primary-100 text-primary-800 dark:bg-primary-950/40 dark:text-primary-300 border border-primary-200 dark:border-primary-800/50 w-fit shadow-2xs'>
                                        <svg class='w-3.5 h-3.5 text-primary' fill='none' viewBox='0 0 24 24' stroke-width='2' stroke='currentColor'><path stroke-linecap='round' stroke-linejoin='round' d='M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5' /></svg>
                                        {$date}
                                    </span>
                                    <span class='inline-flex items-center gap-1.5 px-2 py-0.5 rounded text-[11px] font-semibold bg-amber-50 text-amber-800 dark:bg-amber-950/20 dark:text-amber-300 border border-amber-200/40 dark:border-amber-900/20 w-fit'>
                                        <svg class='w-3.5 h-3.5 text-amber-500' fill='none' viewBox='0 0 24 24' stroke-width='2' stroke='currentColor'><path stroke-linecap='round' stroke-linejoin='round' d='M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z' /></svg>
                                        {$timeRange}
                                    </span>
                                </div>";
                    })
                    ->sortable(['appointment_date', 'appointment_time']),

                TextColumn::make('doctor.user.name')
                    ->label('Doctor')
                    ->sortable()
                    ->description(function ($record) {
                        // Use pre-loaded relationship instead of querying
                        $replacement = $record->doctor->replacements
                            ->firstWhere(function ($rep) use ($record) {
                                return (!$rep->start_date || $rep->start_date <= $record->appointment_date) &&
                                    (!$rep->end_date || $rep->end_date >= $record->appointment_date);
                            });

                        if ($replacement && $replacement->replacementDoctor) {
                            return 'Replaced by: ' . $replacement->replacementDoctor->first_name . ' ' . $replacement->replacementDoctor->last_name;
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

                TextColumn::make('patient.first_name')
                    ->label('Patient')
                    ->sortable(),

                TextColumn::make('patient.existing_patient_id')
                    ->label('Patient ID')
                    ->formatStateUsing(function ($state) {
                        return $state ? $state : 'New patient';
                    })
                    ->sortable(),

                TextColumn::make('booking_email_status')
                    ->label('Booking Email')
                    ->html()
                    ->getStateUsing(function ($record) {
                        $apptStatus = $record?->status instanceof \App\Enums\AppointmentStatus ? $record->status->value : ($record?->status ?? null);
                        $paymentStatus = $record?->payment?->status instanceof \App\Enums\PaymentStatus ? $record->payment->status->value : ($record?->payment?->status ?? null);

                        // Only show for confirmed appointments with paid payments
                        if (! in_array($apptStatus, [\App\Enums\AppointmentStatus::CONFIRMED->value, \App\Enums\AppointmentStatus::COMPLETED->value])) {
                            return '';
                        }
                        if ($paymentStatus !== \App\Enums\PaymentStatus::PAID->value) {
                            return '';
                        }

                        $log = \App\Models\EmailLog::where('appointment_id', $record->id)
                            ->where('type', 'like', '%PatientBookingConfirmation%')
                            ->orderBy('created_at', 'desc')
                            ->first();

                        if (! $log) {
                            return '<span class="text-gray-500">Not sent</span>';
                        }

                        if ($log->status === 'sent') {
                            return '<span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-xs font-semibold bg-emerald-100 text-emerald-800 border border-emerald-200">Sent</span>';
                        }

                        return '<span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-xs font-semibold bg-red-100 text-red-800 border border-red-200">Failed</span>';
                    })
                    ->toggleable(),

                TextColumn::make('consultation_type')
                    ->label('Appointment Mode'),

                getUserAuditColumn('creator', 'Created By'),
                getUserAuditColumn('updater', 'Updated By'),
                getUserAuditColumn('deleter', 'Deleted By'),

                BadgeColumn::make('status')
                    ->label('Appointment Status')
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

                TextColumn::make('id')
                    ->label('Appointment ID')
                    ->limit(15)
                    ->sortable(),
            ])

            ->filters([
                // --------------------------
                // CUSTOM SEARCH INPUT
                // --------------------------
                Filter::make('search')
                    ->form([
                        \Filament\Forms\Components\TextInput::make('query')
                            ->label('Search')
                            ->placeholder('Search by patient, ID, doctor...')
                    ])
                    ->query(function ($query, array $data) {
                        if (empty($data['query'])) {
                            return $query;
                        }
                        $search = $data['query'];

                        // If search query is a UUID, search by exact appointment ID
                        if (\Illuminate\Support\Str::isUuid($search)) {
                            return $query->where('id', $search);
                        }

                        return $query->where(function ($q) use ($search) {
                            // Prefix match on appointment ID for performance
                            $q->where('id', 'like', "{$search}%")
                                ->orWhereHas('patient', function ($pq) use ($search) {
                                    $pq->where('first_name', 'like', "%{$search}%")
                                        ->orWhere('last_name', 'like', "%{$search}%")
                                        ->orWhere(\Illuminate\Support\Facades\DB::raw("CONCAT(first_name, ' ', last_name)"), 'like', "%{$search}%")
                                        ->orWhere('existing_patient_id', 'like', "{$search}%");
                                })
                                ->orWhereHas('doctor', function ($dq) use ($search) {
                                    $dq->where('first_name', 'like', "%{$search}%")
                                        ->orWhere('last_name', 'like', "%{$search}%")
                                        ->orWhere(\Illuminate\Support\Facades\DB::raw("CONCAT(first_name, ' ', last_name)"), 'like', "%{$search}%")
                                        ->orWhereHas('user', function ($uq) use ($search) {
                                            $uq->where('name', 'like', "%{$search}%");
                                        });
                                });
                        });
                    }),

                // --------------------------
                // OPD VISIT DATE FILTER
                // --------------------------
                Filter::make('appointment_date')
                    ->label('OPD Visit Date')
                    ->form([
                        \Filament\Forms\Components\DatePicker::make('date')
                            ->label('OPD Visit Date')
                            ->placeholder('Select date')
                    ])
                    ->query(function ($query, array $data) {
                        if (empty($data['date'])) {
                            return $query;
                        }
                        $date = \Carbon\Carbon::parse($data['date']);
                        return $query->where('appointment_date', $date->toDateString());
                    }),

                // --------------------------
                // DOCTOR FILTER
                // --------------------------
                SelectFilter::make('doctor_id')
                    ->label('Filter by Doctor')
                    ->options(
                        \App\Models\Doctor::query()
                            ->where('status', 'active')
                            ->with('user:id,name')
                            ->orderBy('first_name')
                            ->get()
                            ->mapWithKeys(function ($doctor) {
                                $name = $doctor->user?->name ?: trim("{$doctor->first_name} {$doctor->last_name}");
                                return [$doctor->id => $name];
                            })
                            ->toArray()
                    )
                    ->searchable()
                    ->preload(),

                // --------------------------
                // APPOINTMENT STATUS FILTER
                // --------------------------
                SelectFilter::make('status')
                    ->label('Appointment Status')
                    ->options(collect(AppointmentStatus::cases())->mapWithKeys(fn($status) => [$status->value => $status->label()])->toArray()),

                // --------------------------
                // PAYMENT STATUS FILTER
                // --------------------------
                SelectFilter::make('payment_status')
                    ->label('Payment Status')
                    ->options([
                        ...collect(PaymentStatus::cases())->mapWithKeys(fn($status) => [$status->value => $status->label()])->toArray(),
                        'admin_without_payment' => 'Admin No Payment',
                    ])
                    ->query(function ($query, array $data) {
                        if (empty($data['value'])) {
                            return $query;
                        }

                        if ($data['value'] === 'admin_without_payment') {
                            return $query
                                ->where('booking_source', 'admin')
                                ->where('admin_payment_type', 'without_payment');
                        }

                        return $query->whereHas('payment', fn($q) => $q->where('status', $data['value']));
                    }),

                SelectFilter::make('admin_payment_type')
                    ->label('Admin Payment')
                    ->options([
                        'with_payment' => 'Admin - With Payment',
                        'without_payment' => 'Admin - No Payment',
                    ])
                    ->query(function ($query, array $data) {
                        if (empty($data['value'])) {
                            return $query;
                        }
                        return $query->where('booking_source', 'admin')
                            ->where('admin_payment_type', $data['value']);
                    }),
            ], layout: \Filament\Tables\Enums\FiltersLayout::AboveContent)
            ->filtersFormColumns(6)
            ->deferFilters(false)


            ->extraAttributes([
                'class' => 'custom-pagination custom-appointment-table-cls',
            ])

            ->recordActions([
                ActionGroup::make([
                    ViewAction::make(),
                    \Filament\Actions\Action::make('change_status')
                        ->label('Change Status')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->form([
                            \Filament\Forms\Components\Select::make('status')
                                ->label('Status')
                                ->options(collect(AppointmentStatus::cases())->mapWithKeys(fn($status) => [$status->value => $status->label()])->toArray())
                                ->required(),
                        ])
                        ->mountUsing(fn (Schema $form, $record) => $form->fill([
                            'status' => $record->status instanceof AppointmentStatus ? $record->status->value : $record->status,
                        ]))
                        ->action(function ($record, array $data) {
                            $record->update([
                                'status' => $data['status'],
                            ]);

                            \Filament\Notifications\Notification::make()
                                ->title('Appointment Status Updated')
                                ->body("The appointment status has been updated to " . AppointmentStatus::tryFrom($data['status'])?->label() . ".")
                                ->success()
                                ->send();
                        })
                        ->visible(fn($record) => AppointmentResource::canEdit($record)),
                    \Filament\Actions\Action::make('generate_video_link')
                        ->label('Generate Video Link')
                        ->icon('heroicon-o-link')
                        ->color('warning')
                        ->requiresConfirmation()
                        ->modalHeading('Generate video link')
                        ->modalDescription('Create a Whereby room with host and participant URLs for this appointment.')
                        ->visible(fn ($record): bool => $record->consultation_type === 'video' && !self::hasCompleteVideoLinks($record))
                        ->action(function ($record): void {
                            self::generateVideoLink($record);
                        }),
                    \Filament\Actions\Action::make('view_video_links')
                        ->label('View Video Links')
                        ->icon('heroicon-o-video-camera')
                        ->color('success')
                        ->visible(fn ($record): bool => $record->consultation_type === 'video' && self::hasCompleteVideoLinks($record))
                        ->modalHeading('Video consultation links')
                        ->modalContent(fn ($record) => view('filament.pages.video-consultation-urls', [
                            'videoConsultation' => $record->videoConsultation,
                        ]))
                        ->modalSubmitAction(false)
                        ->modalCancelActionLabel('Close'),
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
                                    $name = trim("{$firstName} {$lastName}");
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
                                    $name = trim("{$firstName} {$lastName}");
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

    protected static function hasCompleteVideoLinks($appointment): bool
    {
        $videoConsultation = $appointment->videoConsultation;

        return $videoConsultation
            && filled($videoConsultation->host_url)
            && (filled($videoConsultation->participate_url) || filled($videoConsultation->room_url));
    }

    protected static function generateVideoLink($appointment, bool $notify = true): ?\App\Models\VideoConsultation
    {
        $wherebyService = app(\App\Services\WherebyService::class);

        if (! $wherebyService->isConfigured()) {
            if ($notify) {
                \Filament\Notifications\Notification::make()
                    ->title('Whereby API not configured')
                    ->body('Add WHEREBY_API_KEY in Settings > Third Party API, then try again.')
                    ->danger()
                    ->send();
            }

            return null;
        }

        $appointment->load('videoConsultation');

        $videoConsultation = $appointment->videoConsultation
            ? $wherebyService->regenerateUrls($appointment->videoConsultation)
            : $wherebyService->createVideoConsultation($appointment);

        if (! $videoConsultation) {
            if ($notify) {
                \Filament\Notifications\Notification::make()
                    ->title('Failed to generate video link')
                    ->body('The Whereby API request failed. Check logs for details.')
                    ->danger()
                    ->send();
            }

            return null;
        }

        if (\Illuminate\Support\Facades\Schema::hasColumn('appointments', 'whereby_room_url')) {
            $appointment->update([
                'whereby_room_url' => $videoConsultation->room_url,
                'whereby_room_id' => $videoConsultation->room_id,
            ]);
        }

        if ($notify) {
            \Filament\Notifications\Notification::make()
                ->title('Video link generated')
                ->body('Host and participant URLs are now available for this appointment.')
                ->success()
                ->send();
        }

        return $videoConsultation;
    }
}
