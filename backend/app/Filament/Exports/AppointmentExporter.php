<?php

namespace App\Filament\Exports;

use App\Models\Appointment;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;
use Illuminate\Support\Number;

class AppointmentExporter extends Exporter
{
    protected static ?string $model = Appointment::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('id')
                ->label('ID'),
            ExportColumn::make('appointment_date')
                ->label('Appointment Date')
                ->state(fn ($record) => \Carbon\Carbon::parse($record->appointment_date)->format('Y-m-d')),
            ExportColumn::make('appointment_time')
                ->label('Time')
                ->state(fn ($record) => \Carbon\Carbon::parse($record->appointment_time)->format('h:i A')),
            ExportColumn::make('patient.user.name')
                ->label('Patient Name'),
            ExportColumn::make('doctor.user.name')
                ->label('Doctor Name'),
            ExportColumn::make('consultation_type')
                ->label('Type')
                ->state(fn ($record) => match ($record->consultation_type instanceof \BackedEnum ? $record->consultation_type->value : (string) $record->consultation_type) {
                    'video' => 'Video',
                    'in_person', 'in-person' => 'In-Person',
                    default => ucfirst((string) $record->consultation_type),
                }),
            ExportColumn::make('status')
                ->label('Appointment Status')
                ->state(fn ($record) => match ($record->status instanceof \BackedEnum ? $record->status->value : (string) $record->status) {
                    'completed' => 'Completed',
                    'scheduled' => 'Scheduled',
                    'cancelled' => 'Cancelled',
                    'no_show', 'no-show' => 'No Show',
                    default => ucfirst((string) $record->status),
                }),
            ExportColumn::make('payment_status')
                ->label('Payment Status')
                ->state(function ($record) {
                    if (!empty($record->status) && in_array($record->status instanceof \BackedEnum ? $record->status->value : $record->status, ['paid', 'failed', 'pending', 'refunded'])) {
                        return ucfirst((string) ($record->status instanceof \BackedEnum ? $record->status->value : $record->status));
                    }
                    if ($record->payment) {
                        $paymentStatus = $record->payment->status ?? 'N/A';
                        if ($paymentStatus instanceof \BackedEnum) {
                            return ucfirst((string) $paymentStatus->value);
                        }
                        return ucfirst((string) $paymentStatus);
                    }
                    return 'N/A';
                }),
            ExportColumn::make('razorpay_payment_id')
                ->label('Payment ID')
                ->state(function ($record) {
                    if (!empty($record->razorpay_payment_id)) {
                        return $record->razorpay_payment_id;
                    }
                    if ($record->payment) {
                        return $record->payment->razorpay_payment_id;
                    }
                    return null;
                }),
            ExportColumn::make('fee_amount')
                ->label('Fee Amount'),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = 'Your appointment export has completed and ' . Number::format($export->successful_rows) . ' ' . str('row')->plural($export->successful_rows) . ' exported.';

        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= ' ' . Number::format($failedRowsCount) . ' ' . str('row')->plural($failedRowsCount) . ' failed to export.';
        }

        return $body;
    }
}
