<?php

namespace App\Filament\Resources\Doctors\Pages;

use App\Filament\Resources\Doctors\DoctorAvailabilityResource;
use App\Imports\DoctorAvailabilityImport;
use App\Models\Doctor;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use App\Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ListDoctorAvailabilities extends ListRecords
{
    protected static string $resource = DoctorAvailabilityResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('downloadTemplate')
                ->label('Download Template')
                ->icon('heroicon-o-document-text')
                ->color('gray')
                ->action(fn () => $this->downloadTemplate()),

            Action::make('bulkImportSlots')
                ->label('Bulk Import Slots')
                ->icon('heroicon-o-arrow-up-tray')
                ->color('primary')
                ->modalHeading('Bulk Import Doctor Slots')
                ->modalDescription('Upload an Excel/CSV file to import availability slots for doctors.')
                ->form([
                    Select::make('doctor_id')
                        ->label('Doctor (Optional)')
                        ->placeholder('Select a doctor if the sheet is for one doctor')
                        ->options(Doctor::with('user')->get()->pluck('user.name', 'id'))
                        ->searchable()
                        ->helperText('If you select a doctor here, all slots in the sheet will be assigned to this doctor. If left empty, the sheet must contain a "Doctor Email" column.'),
                    
                    FileUpload::make('file')
                        ->label('Upload Excel/CSV')
                        ->required()
                        ->acceptedFileTypes([
                            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                            'application/vnd.ms-excel',
                            'text/csv'
                        ])
                        ->disk('local')
                        ->directory('imports')
                        ->preserveFilenames()
                ])
                ->action(function (array $data) {
                    $fileState = $data['file'];
                    $doctorId = $data['doctor_id'] ?? null;
                    
                    try {
                        $filePath = Storage::disk('local')->exists($fileState) 
                            ? Storage::disk('local')->path($fileState) 
                            : $fileState;

                        if (!file_exists($filePath)) {
                            throw new \Exception("The uploaded file could not be found. Please try uploading it again.");
                        }

                        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
                        $readerType = match($extension) {
                            'xlsx' => \Maatwebsite\Excel\Excel::XLSX,
                            'csv' => \Maatwebsite\Excel\Excel::CSV,
                            'xls' => \Maatwebsite\Excel\Excel::XLS,
                            default => null
                        };

                        $importer = new \App\Imports\BulkDoctorAvailabilityImport($doctorId);
                        Excel::import($importer, $filePath, null, $readerType);
                        $results = $importer->getResults();

                        $totalProcessed = $results['totalSaved'] + $results['totalUpdated'] + $results['totalSkipped'];

                        if ($totalProcessed === 0) {
                            Notification::make()
                                ->title('No Data Found')
                                ->body('The file appears to be empty or contains no valid doctor slots.')
                                ->warning()
                                ->send();
                            return;
                        }

                        if ($results['totalSaved'] === 0 && $results['totalUpdated'] === 0) {
                            // Everything failed or was skipped
                            $title = $results['hasErrors'] ? 'Nothing Imported' : 'No New Slots Added';
                            $notificationStyle = $results['hasErrors'] ? 'danger' : 'warning';
                            
                            $body = [];
                            if ($results['totalSkipped'] > 0) {
                                $body[] = "{$results['totalSkipped']} slots were already present in the system and were skipped.";
                            }
                            if ($results['hasErrors'] && !empty($results['errors'])) {
                                $body[] = "Errors found:\n" . implode("\n", array_unique(array_slice($results['errors'], 0, 3)));
                            }

                            Notification::make()
                                ->title($title)
                                ->body(implode("\n\n", $body))
                                ->$notificationStyle()
                                ->persistent()
                                ->send();
                        } else {
                            // Some successes
                            $message = [];
                            if ($results['totalSaved'] > 0) $message[] = "{$results['totalSaved']} created";
                            if ($results['totalUpdated'] > 0) $message[] = "{$results['totalUpdated']} updated";
                            if ($results['totalSkipped'] > 0) $message[] = "{$results['totalSkipped']} already existed (skipped)";

                            $notification = Notification::make()
                                ->title($results['hasErrors'] ? 'Import Partially Successful' : 'Import Successful')
                                ->success();

                            if (!empty($message)) {
                                $notification->body(implode(', ', $message) . '.');
                            }

                            if ($results['hasErrors']) {
                                $notification->warning(); 
                                $errorBody = implode("\n", array_unique(array_slice($results['errors'], 0, 3)));
                                if ($errorBody) {
                                    $notification->body(($notification->getBody() ? $notification->getBody() . "\n\n" : "") . "Note: Some issues occurred during import:\n" . $errorBody);
                                }
                            }

                            $notification->send();
                        }
                    } catch (\Exception $e) {
                        Notification::make()
                            ->title('Import error')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),
        ];
    }

    public function downloadTemplate(): StreamedResponse
    {
        $columns = [
            'Doctor Email',
            'Date (YYYY-MM-DD) or Day Name',
            'Start Time (HH:MM)',
            'End Time (HH:MM)',
            'Capacity',
            'Consultation Type (in-person/video)',
            'OPD Type (general/private)',
            'Doctor Room',
            'Consultation Fee',
            'Is Recurring (true/false)',
            'Recurring Start Date (YYYY-MM-DD)',
            'Recurring End Date (YYYY-MM-DD)',
            'Recurring Months'
        ];

        return response()->streamDownload(function () use ($columns) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, $columns);
            
            // Sample Row
            fputcsv($handle, [
                'doctor@example.com',
                'Monday',
                '09:00',
                '13:00',
                '10',
                'in-person',
                'general',
                'Room 101',
                '500',
                'true',
                date('Y-m-d'),
                date('Y-m-d', strtotime('+3 months')),
                '3'
            ]);

            fclose($handle);
        }, 'doctor_slots_import_template.csv');
    }
}
