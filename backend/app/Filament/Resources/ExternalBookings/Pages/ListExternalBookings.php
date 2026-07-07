<?php

namespace App\Filament\Resources\ExternalBookings\Pages;

use App\Filament\Resources\ExternalBookings\ExternalBookingResource;
use App\Filament\Resources\Pages\ListRecords;
use App\Imports\ExternalBookingsImport;
use App\Models\Doctor;
use App\Services\ExternalBookingSyncService;
use App\Services\GoogleSheetsService;
use App\Services\PaidAppointmentSyncService;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;

class ListExternalBookings extends ListRecords
{
    protected static string $resource = ExternalBookingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            self::syncGoogleSheetAction(),
            self::syncPaidAppointmentsAction(),
            self::importExternalBookingsAction(),
        ];
    }

    public static function syncGoogleSheetAction(): Action
    {
        return Action::make('syncGoogleSheet')
            ->label('Sync Google Sheet')
            ->icon('heroicon-o-arrow-path')
            ->visible(fn () => ExternalBookingResource::canCreate())
            ->modalHeading('Sync external bookings from Google Sheet')
            ->modalDescription('Fetch current rows from the configured Google Sheet and update external bookings.')
            ->form([
                TextInput::make('spreadsheet_id')
                    ->label('Spreadsheet ID')
                    ->default(config('services.google_sheets.spreadsheet_id'))
                    ->required()
                    ->helperText('The ID from the Google Sheet URL.'),
                TextInput::make('range')
                    ->label('Sheet range')
                    ->default(config('services.google_sheets.range', 'A:L'))
                    ->required()
                    ->helperText('Use A:L for the first sheet, or SheetName!A:L for a specific tab.'),
                Select::make('doctor_id')
                    ->label('Doctor override')
                    ->placeholder('Use sheet doctor_id mapping')
                    ->options(fn () => self::doctorOptions())
                    ->searchable()
                    ->preload()
                    ->helperText('Optional. Select this only when the Google Sheet belongs to one doctor.'),
            ])
            ->action(function (
                array $data,
                GoogleSheetsService $sheets,
                ExternalBookingSyncService $syncService
            ): void {
                $rows = $sheets->rows(
                    spreadsheetId: $data['spreadsheet_id'],
                    range: $data['range'],
                );

                $results = $syncService->syncRows(
                    rows: $rows,
                    defaultDoctorId: $data['doctor_id'] ?? null,
                    batchId: (string) Str::uuid(),
                    syncExisting: false,
                    source: 'google_sheet',
                    preferProvidedSourceId: true,
                    updateExisting: false,
                );

                try {
                    $results['sheet_rows_marked_uploaded'] = $sheets->markTrackUploadStatusUploaded(
                        rows: $results['synced_sheet_rows'],
                        spreadsheetId: $data['spreadsheet_id'],
                    );
                } catch (\Throwable $exception) {
                    $results['sheet_rows_marked_uploaded'] = 0;
                    $results['errors'][] = 'Google Sheet status update failed: '.$exception->getMessage();
                }

                self::sendResultsNotification('External bookings synced from Google Sheet', $results);
            });
    }

    public static function syncPaidAppointmentsAction(): Action
    {
        return Action::make('syncPaidAppointments')
            ->label('Sync Paid Appointments')
            ->icon('heroicon-o-circle-stack')
            ->color('gray')
            ->visible(fn () => ExternalBookingResource::canCreate())
            ->modalHeading('Sync from paid appointments database')
            ->modalDescription('Fetch rows from the separate paid_appointments database and sync them into external_bookings.')
            ->requiresConfirmation()
            ->action(function (PaidAppointmentSyncService $paidAppointmentSyncService): void {
                if (! Schema::connection('paid_appointments')->hasTable('paid_appointment')) {
                    Notification::make()
                        ->title('Paid appointment table is missing')
                        ->body('The paid_appointment table does not exist on the paid_appointments database connection.')
                        ->danger()
                        ->send();

                    return;
                }

                $results = $paidAppointmentSyncService->syncExternalPaidAppointments();

                self::sendResultsNotification('External bookings synced from Paid Appointments DB', $results);
            });
    }

    public static function importExternalBookingsAction(): Action
    {
        return Action::make('importExternalBookings')
            ->label('Import External Bookings')
            ->icon('heroicon-o-arrow-up-tray')
            ->color('primary')
            ->visible(fn () => ExternalBookingResource::canCreate())
            ->modalHeading('Import external bookings')
            ->modalDescription('Upload the client Google Sheet export as XLSX, XLS, or CSV.')
            ->form([
                Select::make('doctor_id')
                    ->label('Doctor override')
                    ->placeholder('Use sheet doctor_id mapping')
                    ->options(fn () => self::doctorOptions())
                    ->searchable()
                    ->preload()
                    ->helperText('Optional. Select this only when the uploaded file belongs to one doctor.'),
                FileUpload::make('file')
                    ->label('Sheet file')
                    ->required()
                    ->acceptedFileTypes([
                        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                        'application/vnd.ms-excel',
                        'text/csv',
                    ])
                    ->disk('local')
                    ->directory('imports/external-bookings')
                    ->preserveFilenames(),
            ])
            ->action(function (array $data): void {
                $fileState = $data['file'];
                $filePath = Storage::disk('local')->exists($fileState)
                    ? Storage::disk('local')->path($fileState)
                    : $fileState;

                $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
                $readerType = match ($extension) {
                    'xlsx' => \Maatwebsite\Excel\Excel::XLSX,
                    'xls' => \Maatwebsite\Excel\Excel::XLS,
                    'csv' => \Maatwebsite\Excel\Excel::CSV,
                    default => null,
                };

                $import = new ExternalBookingsImport(
                    defaultDoctorId: $data['doctor_id'] ?? null,
                    batchId: (string) Str::uuid(),
                );

                Excel::import($import, $filePath, null, $readerType);
                $results = $import->results();

                self::sendResultsNotification('External bookings imported', $results);
            });
    }

    private static function doctorOptions(): array
    {
        return Doctor::with('user:id,name')
            ->orderBy('first_name')
            ->get()
            ->mapWithKeys(fn (Doctor $doctor) => [
                $doctor->id => $doctor->user?->name ?: trim("{$doctor->first_name} {$doctor->last_name}"),
            ])
            ->toArray();
    }

    private static function sendResultsNotification(string $title, array $results): void
    {
        $body = "Created: {$results['created']}, Updated: {$results['updated']}, Existing skipped: ".($results['unchanged'] ?? 0).", Invalid skipped: {$results['skipped']}.";

        if (array_key_exists('sheet_rows_marked_uploaded', $results)) {
            $body .= " Sheet rows marked Uploaded: {$results['sheet_rows_marked_uploaded']}.";
        }

        if ($results['errors']) {
            $body .= "\n".implode("\n", array_slice($results['errors'], 0, 5));
        }

        Notification::make()
            ->title($title)
            ->body($body)
            ->success()
            ->send();
    }
}