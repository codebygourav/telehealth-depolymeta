<?php

namespace App\Console\Commands;

use App\Services\ExternalBookingSyncService;
use App\Services\GoogleSheetsService;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class SyncExternalBookingsFromGoogleSheet extends Command
{
    protected $signature = 'external-bookings:sync-google-sheet
        {--spreadsheet-id= : Override the configured Google spreadsheet ID}
        {--range= : Override the configured sheet range, for example Sheet1!A:L}
        {--doctor-id= : Force all synced rows to one platform doctor}
        {--dry-run : Fetch and validate Google Sheet access without saving bookings}';

    protected $description = 'Sync external bookings from the configured Google Sheet';

    public function handle(GoogleSheetsService $sheets, ExternalBookingSyncService $syncService): int
    {
        $rows = $sheets->rows(
            spreadsheetId: $this->option('spreadsheet-id') ?: null,
            range: $this->option('range') ?: null,
        );

        if ($this->option('dry-run')) {
            $this->info('Google Sheet connection successful.');
            $this->info('Fetched rows: '.count($rows));

            foreach (array_slice($rows, 0, 3) as $index => $row) {
                $this->line(sprintf(
                    'Row %d: %s | %s | %s | %s',
                    $row['__sheet_row_number'] ?? $index + 2,
                    $row['patient_name'] ?? 'No patient',
                    $row['appointment_date'] ?? 'No date',
                    $row['time_slot'] ?? 'No time',
                    $row['doctor_name'] ?? 'No doctor',
                ));
            }

            return self::SUCCESS;
        }

        $results = $syncService->syncRows(
            rows: $rows,
            defaultDoctorId: $this->option('doctor-id') ?: null,
            batchId: (string) Str::uuid(),
            syncExisting: false,
            source: 'google_sheet',
            preferProvidedSourceId: true,
            updateExisting: false,
        );

        try {
            $results['sheet_rows_marked_uploaded'] = $sheets->markTrackUploadStatusUploaded($results['synced_sheet_rows']);
        } catch (\Throwable $exception) {
            $results['sheet_rows_marked_uploaded'] = 0;
            $results['errors'][] = 'Google Sheet status update failed: '.$exception->getMessage();
        }

        $this->info("External bookings synced. Created: {$results['created']}, Updated: {$results['updated']}, Existing skipped: {$results['unchanged']}, Invalid skipped: {$results['skipped']}, Sheet rows marked Uploaded: {$results['sheet_rows_marked_uploaded']}.");

        foreach (array_slice($results['errors'], 0, 10) as $error) {
            $this->warn($error);
        }

        if (count($results['errors']) > 10) {
            $this->warn('Additional errors were omitted from console output.');
        }

        return $results['skipped'] > 0 ? self::FAILURE : self::SUCCESS;
    }
}
