<?php

namespace App\Filament\Resources\EmailLogs;

use App\Filament\Resources\EmailLogs\Pages\ListEmailLogs;
use App\Filament\Resources\EmailLogs\Pages\ViewEmailLog;
use App\Filament\Resources\EmailLogs\Widgets\EmailLogStatsWidget;
use App\Models\EmailLog;
use App\Traits\{HasCustomSidebar, HasResourcePermissions};
use Filament\Schemas\Components\Section as InfoSection;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class EmailLogResource extends Resource
{
    use HasResourcePermissions;
    use HasCustomSidebar;

    protected static ?string $slug = 'email-logs';
    protected static ?string $model = EmailLog::class;
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-envelope';
    protected static string|\BackedEnum|null $activeNavigationIcon = 'heroicon-s-envelope';
    protected static string|\UnitEnum|null $navigationGroup = 'System & Settings';
    protected static ?int $navigationSort = 95;
    protected static ?string $recordTitleAttribute = 'type_display_name';
    protected static ?string $label = 'Email Log';
    protected static ?string $pluralLabel = 'Email Logs';

    public static function getSidebarOptions(): array
    {
        return [
            'label'   => 'Email Logs',
            'icon'    => 'heroicon-o-envelope',
            'sort'    => 95,
            'group'   => 'System & Settings',
            'visible' => true,
        ];
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canViewAny();
    }

    public static function canCreate(): bool
    {
        return false;
    }
    public static function canEdit($record): bool
    {
        return false;
    }
    public static function canDelete($record): bool
    {
        return false;
    }

    // ── Table ───────────────────────────────────────────────────────────────

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('created_at')
                    ->label('Date & Time')
                    ->dateTime('d M Y, H:i:s')
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('type')
                    ->label('Email Type')
                    ->formatStateUsing(fn(string $state): string => trim(
                        preg_replace('/([A-Z])/', ' $1', preg_replace('/Mail$/', '', class_basename($state)))
                    ))
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->size('md')
                    ->color(fn(string $state): string => match (true) {
                        str_contains($state, 'PatientBookingConfirmation') => 'success',
                        str_contains($state, 'PatientCredentials') => 'info',
                        str_contains($state, 'PatientRegistration') => 'info',
                        str_contains($state, 'PatientNextAppointment') => 'info',
                        str_contains($state, 'AdminBookingAlert') => 'warning',
                        str_contains($state, 'TransactionPaid') => 'purple',
                        str_contains($state, 'DoctorCredentials') => 'secondary',
                        default                         => 'gray',
                    }),

                TextColumn::make('recipient_with_patient_line')
                    ->label('Patient')
                    ->html()
                    ->copyable()
                    ->copyMessage('Patient info copied')

                    ->wrap()
                    ->getStateUsing(function ($record) {
                        if (! str_contains($record?->type ?? '', 'PatientBookingConfirmation')) {
                            return '';
                        }

                        $appointment = $record?->appointment;
                        if (! $appointment || ! $appointment->patient) {
                            return '<span class="text-gray-500 text-sm">Patient info not available</span>';
                        }

                        $patient = $appointment->patient;
                        $patientName = trim(($patient->first_name ?? '') . ' ' . ($patient->last_name ?? '')) ?: ($patient->name ?? $patient->user?->name ?? 'N/A');
                        $patientEmail = $patient->email ?? $patient->email ?? $patient->user?->email ?? 'N/A';
                        $unitId = $patient->existing_patient_id ?? $patient->existing_patient_id ?? $patient->existing_patient_id ?? 'N/A';

                        return '<div class="space-y-1">' .
                            '<div class="font-medium text-gray-900">' . e($patientEmail) . '</div>' .
                            '<div class="text-xs text-gray-900">' . e($patientName) . ' (<span class="text-xs text-gray-500">Unit Id: ' . e($unitId) . '</span>)</div>' .
                            '</div>';
                    }),

                TextColumn::make('subject')
                    ->label('Subject')
                    ->limit(50)
                    ->toggleable(),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'sent'   => 'success',
                        'failed' => 'danger',
                        default  => 'gray',
                    })
                    ->icon(fn(string $state): string => match ($state) {
                        'sent'   => 'heroicon-m-check-circle',
                        'failed' => 'heroicon-m-x-circle',
                        default  => 'heroicon-m-question-mark-circle',
                    })
                    ->sortable(),

                TextColumn::make('attempt')
                    ->label('Attempt #')
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('error_message')
                    ->label('Error')
                    ->limit(60)
                    ->wrap()
                    ->color('danger')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'sent'   => '✅ Sent',
                        'failed' => '❌ Failed',
                    ]),

                SelectFilter::make('type')
                    ->label('Email Type')
                    ->options([
                        'App\Mail\PatientBookingConfirmationMail'  => 'Patient Booking Confirmation',
                        'App\Mail\AdminBookingAlertMail'           => 'Admin Booking Alert',
                        'App\Mail\TransactionPaidNotificationMail' => 'Transaction Paid',
                        'App\Mail\PatientCredentialsMail'          => 'Patient Credentials',
                        'App\Mail\DoctorCredentialsMail'           => 'Doctor Credentials',
                        'App\Mail\PatientRegistrationCompleteMail' => 'Patient Registration',
                        'App\Mail\PatientNextAppointmentMail'      => 'Next Appointment Reminder',
                    ]),

                SelectFilter::make('period')
                    ->label('Date Range')
                    ->options([
                        'today'      => 'Today',
                        'this_week'  => 'This Week',
                        'this_month' => 'This Month',
                    ])
                    ->default('all')
                    ->indicator(fn($state): string => match (true) {
                        is_array($state) && ($state['value'] ?? null) === 'today'      => 'Today',
                        is_array($state) && ($state['value'] ?? null) === 'this_week'  => 'This Week',
                        is_array($state) && ($state['value'] ?? null) === 'this_month' => 'This Month',
                        $state === 'today'                                             => 'Today',
                        $state === 'this_week'                                         => 'This Week',
                        $state === 'this_month'                                        => 'This Month',
                        default                                                         => 'All',
                    })
                    ->query(fn(Builder $query, array $state): Builder => match ($state['value'] ?? null) {
                        'today'      => $query->today(),
                        'this_week'  => $query->thisWeek(),
                        'this_month' => $query->thisMonth(),
                        default      => $query,
                    }),

                Filter::make('failed_only')
                    ->label('Failed Only')
                    ->query(fn(Builder $q, $state) => (
                        (is_array($state) ? ($state['value'] ?? $state) : $state)
                    ) ? $q->where('status', 'failed') : $q)
                    ->toggle(),
            ])
            ->striped()
            ->paginated([25, 50, 100])
            ->poll('30s');
    }

    // ── Infolist (detail view) ───────────────────────────────────────────────

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            InfoSection::make('Email Details')
                ->icon('heroicon-o-envelope')
                ->columns(2)
                ->schema([
                    TextEntry::make('type')
                        ->label('Email Type')
                        ->formatStateUsing(fn(string $state): string => trim(
                            preg_replace('/([A-Z])/', ' $1', preg_replace('/Mail$/', '', class_basename($state)))
                        ))
                        ->badge()
                        ->color('info'),

                    TextEntry::make('status')
                        ->label('Status')
                        ->badge()
                        ->color(fn(string $state): string => match ($state) {
                            'sent'   => 'success',
                            'failed' => 'danger',
                            default  => 'gray',
                        }),

                    TextEntry::make('to_email')
                        ->label('Recipient Email')
                        ->copyable(),

                    TextEntry::make('patient_line')
                        ->label('Patient')
                        ->columnSpanFull()
                        ->html()
                        ->formatStateUsing(fn(?string $state): string => $state ? '<span class="text-sm text-gray-600">' . e($state) . '</span>' : '<span class="text-gray-500">No patient info available.</span>'),

                    TextEntry::make('receipt')
                        ->label('Receipt')
                        ->html()
                        ->columnSpan(2)
                        ->formatStateUsing(function ($state, $record): string {
                            $path = $record?->payment?->receipt_pdf;
                            if (! $path) {
                                return '<span class="text-gray-500">No receipt attached</span>';
                            }

                            $url = \Illuminate\Support\Facades\Storage::disk('public')->url($path);

                            return '<div>' .
                                '<a href="' . $url . '" target="_blank" class="text-indigo-600 underline">Download payment receipt (PDF)</a>' .
                                '<div class="mt-2">' .
                                '<iframe src="' . $url . '" style="width:100%;height:300px;border:1px solid #e5e7eb;border-radius:6px;" sandbox></iframe>' .
                                '</div>' .
                                '</div>';
                        }),

                    TextEntry::make('subject')
                        ->label('Subject')
                        ->columnSpanFull(),

                    TextEntry::make('attempt')
                        ->label('Attempt Number'),

                    TextEntry::make('sent_at')
                        ->label('Sent At')
                        ->dateTime('d M Y H:i:s')
                        ->placeholder('—')
                        ->visible(fn($record) => $record?->status === 'sent'),

                    TextEntry::make('failed_at')
                        ->label('Failed At')
                        ->dateTime('d M Y H:i:s')
                        ->placeholder('—')
                        ->visible(fn($record) => $record?->status === 'failed'),

                    TextEntry::make('created_at')
                        ->label('Logged At')
                        ->dateTime('d M Y H:i:s'),

                    TextEntry::make('appointment_id')
                        ->label('Appointment ID')
                        ->copyable()
                        ->columnSpan(1)
                        ->placeholder('—'),

                    TextEntry::make('payment_id')
                        ->label('Payment ID')
                        ->copyable()
                        ->columnSpan(1)
                        ->placeholder('—'),
                ]),

            /* Linked Records moved into Email Details for a compact single section */

            InfoSection::make('Rendered Email')
                ->icon('heroicon-o-document-text')
                ->schema([
                    TextEntry::make('rendered_html_body')
                        ->label('Email Template')
                        ->html()
                        ->columnSpanFull()
                        ->placeholder('Rendered email body is not available.')
                        ->formatStateUsing(fn(?string $state): string => $state
                            ? '<div class="overflow-x-auto rounded-lg border border-gray-200 bg-white p-4">' . $state . '</div>'
                            : '<p class="text-gray-500">Rendered email body is not available. It may have failed to render or was not captured.</p>'),
                ]),

            InfoSection::make('Error Details')
                ->icon('heroicon-o-exclamation-triangle')
                ->schema([
                    TextEntry::make('error_message')
                        ->label('Error Message')
                        ->columnSpanFull()
                        ->color('danger')
                        ->placeholder('No error — email was delivered successfully.')
                        ->prose(),
                ])
                ->visible(fn($record) => ($record?->status === 'failed') || filled($record?->error_message)),
        ]);
    }

    // ── Relations / Pages ────────────────────────────────────────────────────

    public static function getRelations(): array
    {
        return [];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with([
            'appointment.patient',
            'payment.appointment.patient',
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListEmailLogs::route('/'),
            'view'  => ViewEmailLog::route('/{record}'),
        ];
    }

    public static function getWidgets(): array
    {
        return [EmailLogStatsWidget::class];
    }
}