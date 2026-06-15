<?php

namespace App\Filament\Resources\Payments\Tables;

use App\Enums\PaymentStatus;
use App\Enums\AppointmentStatus;
use Filament\Tables\Table;
use Filament\Tables\Columns\{TextColumn, BadgeColumn};
use Filament\Tables\Filters\{Filter, SelectFilter};
use Filament\Actions\{ActionGroup, BulkActionGroup, DeleteBulkAction, ForceDeleteBulkAction, RestoreBulkAction, ViewAction, DeleteAction};
use App\Filament\Resources\Payments\PaymentResource;

class PaymentsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->searchable(false)
            ->modifyQueryUsing(function ($query) {
                return $query->with([
                    'appointment:id,patient_id,doctor_id,appointment_date,appointment_time,appointment_end_time,status,consultation_type,slug',
                    'appointment.patient:id,user_id,first_name,last_name,existing_patient_id,mobile_no,email',
                    'appointment.patient.user:id,name,email,phone',
                    'appointment.doctor:id,user_id,first_name,last_name',
                    'appointment.doctor.user:id,name,email',
                    'creator:id,name',
                ]);
            })
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('created_at')
                    ->label('Payment Created On')
                    ->html()
                    ->formatStateUsing(function ($record) {
                        if (!$record || !$record->created_at) {
                            return '-';
                        }

                        $carbon = \Carbon\Carbon::parse($record->created_at);
                        $date = $carbon->format('M d, Y');
                        $time = $carbon->format('h:i A');
                        $isTodayBadge = $carbon->isToday()
                            ? "<span class='inline-flex items-center px-1.5 py-0.5 rounded text-[9px] font-black bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300 border border-emerald-300 dark:border-emerald-800 uppercase tracking-wider animate-pulse ml-1.5'>New Today</span>"
                            : '';

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

                BadgeColumn::make('status')
                    ->label('Payment Status')
                    ->formatStateUsing(fn($state) => $state instanceof PaymentStatus ? $state->label() : ucfirst((string) $state))
                    ->color(fn($state) => match ($state instanceof PaymentStatus ? $state->value : $state) {
                        PaymentStatus::PAID->value => 'success',
                        PaymentStatus::PENDING->value => 'warning',
                        PaymentStatus::FAILED->value => 'danger',
                        PaymentStatus::REFUNDED->value => 'gray',
                        default => 'info',
                    })
                    ->sortable(),

                TextColumn::make('appointment.appointment_date')
                    ->label('OPD Visit Date')
                    ->html()
                    ->formatStateUsing(function ($record) {
                        $appointment = $record->appointment;

                        if (!$appointment?->appointment_date) {
                            return '-';
                        }

                        $date = \Carbon\Carbon::parse($appointment->appointment_date)->format('M d, Y');
                        $startTime = $appointment->appointment_time
                            ? \Carbon\Carbon::parse($appointment->appointment_time)->format('h:i A')
                            : '';
                        $endTime = $appointment->appointment_end_time
                            ? \Carbon\Carbon::parse($appointment->appointment_end_time)->format('h:i A')
                            : '';
                        $timeRange = trim("{$startTime} - {$endTime}", ' -');

                        return "<div class='flex flex-col gap-1.5 py-1'>
                                    <span class='inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-bold bg-primary-100 text-primary-800 dark:bg-primary-950/40 dark:text-primary-300 border border-primary-200 dark:border-primary-800/50 w-fit shadow-2xs'>
                                        {$date}
                                    </span>
                                    <span class='inline-flex items-center gap-1.5 px-2 py-0.5 rounded text-[11px] font-semibold bg-amber-50 text-amber-800 dark:bg-amber-950/20 dark:text-amber-300 border border-amber-200/40 dark:border-amber-900/20 w-fit'>
                                        {$timeRange}
                                    </span>
                                </div>";
                    })
                    ->sortable(),

                TextColumn::make('appointment.doctor.user.name')
                    ->label('Doctor')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('appointment.patient.first_name')
                    ->label('Patient')
                    ->state(fn($record) => $record->appointment && $record->appointment->patient
                        ? trim(($record->appointment->patient->first_name ?? '') . ' ' . ($record->appointment->patient->last_name ?? ''))
                        : 'Unknown Patient')
                    ->searchable(query: function ($query, $search) {
                        $query->whereHas('appointment.patient', function ($q) use ($search) {
                            $q->where('first_name', 'like', "%{$search}%")
                                ->orWhere('last_name', 'like', "%{$search}%");
                        });
                    })
                    ->sortable(),

                TextColumn::make('appointment.patient.existing_patient_id')
                    ->label('Patient Unit ID')
                    ->formatStateUsing(fn($state) => $state ?: 'New patient')
                    ->sortable(),

                TextColumn::make('amount')
                    ->label('Amount')
                    ->money('INR')
                    ->sortable(),

                TextColumn::make('payment_method')
                    ->label('Method')
                    ->sortable(),

                BadgeColumn::make('appointment.status')
                    ->label('Appointment Status')
                    ->formatStateUsing(function ($state) {
                        if (!$state) return 'No Appointment';
                        $statusEnum = $state instanceof AppointmentStatus
                            ? $state
                            : AppointmentStatus::tryFrom($state);

                        return $statusEnum
                            ? $statusEnum->label()
                            : (is_string($state) ? ucfirst($state) : '');
                    })
                    ->color(function ($state) {
                        if (!$state) return 'secondary';
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

                TextColumn::make('razorpay_payment_id')
                    ->label('Payment ID')
                    ->limit(20)
                    ->copyable()
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('appointment_id')
                    ->label('Appointment ID')
                    ->copyable()
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('creator.name')
                    ->label('Created By')
                    ->formatStateUsing(function ($state, $record) {
                        $user = $record->creator;
                        if (!$user) return 'System/Seeder';

                        return $user->name;
                    })
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Filter::make('search')
                    ->form([
                        \Filament\Forms\Components\TextInput::make('query')
                            ->label('Search')
                            ->placeholder('Search by patient, payment ID, appointment ID, doctor...')
                    ])
                    ->query(function ($query, array $data) {
                        if (empty($data['query'])) {
                            return $query;
                        }

                        $search = $data['query'];

                        return $query->where(function ($q) use ($search) {
                            $q->where('id', 'like', "%{$search}%")
                                ->orWhere('transaction_id', 'like', "%{$search}%")
                                ->orWhere('razorpay_payment_id', 'like', "%{$search}%")
                                ->orWhere('razorpay_order_id', 'like', "%{$search}%")
                                ->orWhere('appointment_id', 'like', "%{$search}%")
                                ->orWhereHas('appointment.patient', function ($pq) use ($search) {
                                    $pq->where('first_name', 'like', "%{$search}%")
                                        ->orWhere('last_name', 'like', "%{$search}%")
                                        ->orWhere('existing_patient_id', 'like', "%{$search}%");
                                })
                                ->orWhereHas('appointment.doctor', function ($dq) use ($search) {
                                    $dq->where('first_name', 'like', "%{$search}%")
                                        ->orWhere('last_name', 'like', "%{$search}%")
                                        ->orWhereHas('user', function ($uq) use ($search) {
                                            $uq->where('name', 'like', "%{$search}%");
                                        });
                                });
                        });
                    }),

                Filter::make('created_at')
                    ->label('Payment Date')
                    ->form([
                        \Filament\Forms\Components\DatePicker::make('date')
                            ->label('Payment Date')
                            ->placeholder('Select date')
                    ])
                    ->query(function ($query, array $data) {
                        if (empty($data['date'])) {
                            return $query;
                        }

                        return $query->whereDate('created_at', $data['date']);
                    }),

                SelectFilter::make('doctor_id')
                    ->label('Filter by Doctor')
                    ->options(
                        \App\Models\Doctor::query()
                            ->with('user:id,name')
                            ->orderBy('first_name')
                            ->get()
                            ->mapWithKeys(function ($doctor) {
                                $name = $doctor->user?->name ?: trim("{$doctor->first_name} {$doctor->last_name}");

                                return [$doctor->id => $name];
                            })
                            ->toArray()
                    )
                    ->query(function ($query, array $data) {
                        if (empty($data['value'])) {
                            return $query;
                        }

                        return $query->whereHas('appointment', fn($q) => $q->where('doctor_id', $data['value']));
                    })
                    ->searchable()
                    ->preload(),

                SelectFilter::make('appointment_status')
                    ->label('Appointment Status')
                    ->options(collect(AppointmentStatus::cases())->mapWithKeys(fn($status) => [$status->value => $status->label()])->toArray())
                    ->query(function ($query, array $data) {
                        if (empty($data['value'])) {
                            return $query;
                        }

                        return $query->whereHas('appointment', fn($q) => $q->where('status', $data['value']));
                    }),

                SelectFilter::make('status')
                    ->label('Payment Status')
                    ->options(collect(PaymentStatus::cases())->mapWithKeys(fn($status) => [$status->value => $status->label()])->toArray()),
                SelectFilter::make('payment_method')
                    ->label('Payment Method')
                    ->options([
                        'card' => 'Card',
                        'upi' => 'UPI',
                        'netbanking' => 'Net Banking',
                        'wallet' => 'Wallet',
                    ]),
            ], layout: \Filament\Tables\Enums\FiltersLayout::AboveContent)
            ->filtersFormColumns(6)
            ->deferFilters(false)
            ->extraAttributes([
                'class' => 'custom-pagination custom-appointment-table-cls',
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->visible(fn() => PaymentResource::canDeleteAny()),
                    ForceDeleteBulkAction::make()
                        ->visible(fn() => PaymentResource::canDeleteAny()),
                    RestoreBulkAction::make()
                        ->visible(fn() => PaymentResource::canEdit(null)),
                ]),
            ])
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make()->visible(fn() => PaymentResource::canView(null)),
                    DeleteAction::make()->visible(fn() => PaymentResource::canDeleteAny())->requiresConfirmation(),
                ]),
            ])
            ->recordUrl(null)
            ->defaultSort('created_at', 'desc');
    }
}
