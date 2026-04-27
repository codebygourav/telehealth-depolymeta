<?php

namespace App\Filament\Resources\Leaves\Pages;

use App\Filament\Resources\Leaves\LeaveResource;
use Filament\Resources\Pages\CreateRecord;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Log;

class CreateLeave extends CreateRecord
{
    protected static string $resource = LeaveResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Always try to extract dates from date_range first (most reliable source)
        if (!empty($data['date_range'])) {
            if (is_array($data['date_range'])) {
                // Direct array access
                if (isset($data['date_range']['start_date'])) {
                    $data['start_date'] = $data['date_range']['start_date'];
                }
                if (isset($data['date_range']['end_date'])) {
                    $data['end_date'] = $data['date_range']['end_date'];
                }
            } elseif (is_string($data['date_range'])) {
                // Try to decode as JSON
                $decoded = json_decode($data['date_range'], true);
                if (is_array($decoded)) {
                    if (isset($decoded['start_date'])) {
                        $data['start_date'] = $decoded['start_date'];
                    }
                    if (isset($decoded['end_date'])) {
                        $data['end_date'] = $decoded['end_date'];
                    }
                }
            }
        }

        // Debug: Log the data to see what we're getting
        Log::debug('Leave creation data BEFORE processing', [
            'start_date' => $data['start_date'] ?? 'missing',
            'end_date' => $data['end_date'] ?? 'missing',
            'date_range' => $data['date_range'] ?? 'missing',
            'date_range_type' => isset($data['date_range']) ? gettype($data['date_range']) : 'not set',
        ]);

        // Clean up dates - remove empty strings and normalize
        if (isset($data['start_date']) && ($data['start_date'] === '' || $data['start_date'] === null)) {
            $data['start_date'] = null;
        }
        if (isset($data['end_date']) && ($data['end_date'] === '' || $data['end_date'] === null)) {
            $data['end_date'] = null;
        }

        // Final validation - if dates are still missing, throw validation error
        if (empty($data['start_date']) || empty($data['end_date'])) {
            Log::error('Leave creation failed - dates are missing', [
                'start_date' => $data['start_date'] ?? 'missing',
                'end_date' => $data['end_date'] ?? 'missing',
                'date_range' => $data['date_range'] ?? 'missing',
                'all_data_keys' => array_keys($data),
            ]);

            Notification::make()
                ->title('Validation Error')
                ->body('Please select both start and end dates for the leave duration.')
                ->danger()
                ->persistent()
                ->send();

            // Throw validation exception to prevent database insert
            throw \Illuminate\Validation\ValidationException::withMessages([
                'date_range' => ['Please select both start and end dates for the leave duration.'],
            ]);
        }

        // Remove date_range as it's not a database field
        unset($data['date_range']);

        Log::debug('Leave creation data AFTER processing', [
            'start_date' => $data['start_date'],
            'end_date' => $data['end_date'],
        ]);

        return $data;
    }

    protected function afterCreate(): void
    {
        $record = $this->record;

        // Use NotificationService to send mobile notification if created as approved
        try {
            $sent = \App\Services\NotificationService::notifyLeaveAdded($record);

            if ($sent) {
                // Show notification on admin site that it was shared with the user
                Notification::make()
                    ->title('Notification Shared')
                    ->body("This leave has been shared with {$record->user->name} via mobile notification.")
                    ->success()
                    ->send();
            }
        } catch (\Exception $e) {
            Log::error('Failed to send leave notification: ' . $e->getMessage());
        }
    }
}
