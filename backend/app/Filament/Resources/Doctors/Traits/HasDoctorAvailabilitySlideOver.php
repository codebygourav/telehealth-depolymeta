<?php

namespace App\Filament\Resources\Doctors\Traits;

use App\Enums\DayOfWeek;
use App\Filament\Resources\Doctors\Schemas\DoctorForm;
use App\Models\DoctorAvailability;
use App\Services\DoctorAvailabilityValidationService;
use Filament\Notifications\Notification;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Filament\Forms\Components\FileUpload;
use Maatwebsite\Excel\Facades\Excel;
use App\{Exports\DoctorAvailabilityExport, Imports\DoctorAvailabilityImport};
use Illuminate\Support\Facades\Storage;
use Filament\Schemas\Components\{Section};
use Filament\Actions\{ActionGroup, Action};


trait HasDoctorAvailabilitySlideOver
{

    protected function exportSlotsAction(): Action
    {
        return Action::make('exportSlots')
            ->label('Export Slots')
            ->icon('heroicon-o-arrow-down-tray')
            ->color('success')
            ->action(function () {
                return Excel::download(
                    new DoctorAvailabilityExport($this->record->id),
                    'doctor-slots.xlsx'
                );
            });
    }

    protected function downloadSampleAction(): Action
    {
        return Action::make('downloadSample')
            ->label('Download Sample')
            ->color('gray')
            ->icon('heroicon-o-document-arrow-down')
            ->action(function () {
                $headings = [
                    'day_of_week',
                    'date',
                    'start_time',
                    'end_time',
                    'capacity',
                    'consultation_type',
                    'opd_type',
                    'doctor_room',
                    'consultation_fee',
                    'is_recurring',
                    'recurring_start_date',
                    'recurring_end_date',
                    'recurring_months',
                ];

                return \Maatwebsite\Excel\Facades\Excel::download(
                    new class($headings) implements \Maatwebsite\Excel\Concerns\FromCollection, \Maatwebsite\Excel\Concerns\WithHeadings {
                        protected $headings;
                        public function __construct($headings) { $this->headings = $headings; }
                        public function collection() {
                            return collect([
                                [
                                    'Monday',
                                    null,
                                    '09:00',
                                    '13:00',
                                    '10',
                                    'in-person',
                                    'general',
                                    'Room 101',
                                    '500',
                                    '1',
                                    now()->format('Y-m-d'),
                                    now()->addMonths(3)->format('Y-m-d'),
                                    '3'
                                ],
                                [
                                    'Tuesday',
                                    now()->addDay()->format('Y-m-d'),
                                    '10:00',
                                    '12:00',
                                    '5',
                                    'video',
                                    '',
                                    'Virtual Room',
                                    '1000',
                                    '0',
                                    null,
                                    null,
                                    null
                                ],
                                [
                                    'Wednesday',
                                    null,
                                    '14:00',
                                    '18:00',
                                    '8',
                                    'in-person',
                                    'general',
                                    'Room 102',
                                    '750',
                                    '1',
                                    null,
                                    null,
                                    '6'
                                ]
                            ]);
                        }
                        public function headings(): array { return $this->headings; }
                    },
                    'doctor-slots-sample.xlsx'
                );
            });
    }

    protected function importSlotsAction(): Action
    {
        return Action::make('importSlots')
            ->label('Import Slots')
            ->icon('heroicon-o-arrow-up-tray')
            ->color('primary')
            ->modalHeading('Import Doctor Slots')
            ->form([
                FileUpload::make('file')
                    ->label('Upload Excel')
                    ->required()
                    ->acceptedFileTypes([
                        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                        'text/csv'
                    ])
                    ->disk('local')           // ✅ REQUIRED
                    ->directory('imports')    // ✅ REQUIRED
                    ->preserveFilenames()
            ])
            ->action(function (array $data) {
                $filePath = Storage::disk('local')->path($data['file']);
                $importer = new \App\Imports\DoctorAvailabilityImport($this->record, $this);

                \Maatwebsite\Excel\Facades\Excel::import($importer, $filePath);

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
                    $title = $results['hasErrors'] ? 'Nothing Imported' : 'No New Slots Added';
                    $notificationStyle = $results['hasErrors'] ? 'danger' : 'warning';

                    $body = [];
                    if ($results['totalSkipped'] > 0) {
                        $body[] = "{$results['totalSkipped']} slots were already present and skipped.";
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

                    // Refresh the form data to show the imported slots in the slide-over
                    $currentState = method_exists($this->form, 'getRawState')
                        ? $this->form->getRawState()
                        : $this->form->getState();

                    $this->form->fill(array_merge(
                        $currentState,
                        $this->getAvailabilitySlotsData()
                    ));
                }

            });
    }
    protected function availabilitySlideOverAction(): Action
    {
        return Action::make('manageAvailability')
            ->label('Manage Availability')
            ->icon('heroicon-o-calendar-days')
            ->slideOver()
            ->modalWidth('6xl')
            ->modalCloseButton(true)
            ->modalHeading('Manage Availability')
            ->modalDescription('Set up this doctor\'s available consultation times.')


            ->disabled(fn() => filled($this->getAvailabilityActionDisabledReason()))
            ->tooltip(fn() => $this->getAvailabilityActionDisabledReason())

            ->extraAttributes(fn() => filled($this->getAvailabilityActionDisabledReason())
                ? [
                    'style' => 'background-color: #e5e7eb !important; border-color: #d1d5db !important; color: #6b7280 !important;',
                ]
                : [])


            ->mountUsing(function (Action $action, $form) {
                try {
                    $disabledReason = $this->getAvailabilityActionDisabledReason();

                    if (filled($disabledReason)) {
                        Notification::make()
                            ->title('Profile Incomplete')
                            ->body($disabledReason)
                            ->danger()
                            ->send();

                        $action->cancel();
                        $action->halt();
                        return;
                    }

                    $state = method_exists($this->form, 'getRawState')
                        ? $this->form->getRawState()
                        : $this->form->getState();

                    $data = array_merge($state, $this->getAvailabilitySlotsData());


                    $form->fill($data);
                } catch (\Throwable $e) {
                    Notification::make()
                        ->title('Cannot Open Availability')
                        ->danger()
                        ->send();

                    $action->cancel();
                    $action->halt();
                }
            })

            ->form(fn() => [
                \Filament\Schemas\Components\Actions::make([
                    ActionGroup::make([
                        $this->exportSlotsAction(),
                        $this->importSlotsAction(),
                        $this->downloadSampleAction(),
                    ])
                    ->label('Import / Export')
                    ->icon('heroicon-o-arrow-path')
                    ->button()
                    ->size('sm')
                    ->color('primary'),
                ])
                    ->alignEnd()
                    ->columnSpanFull(),

                ...DoctorForm::availabilityTabsForSlideOver(),
            ])


            ->modalSubmitAction(false)
            ->modalCancelAction(false)
            ->action(fn(array $data) => $this->saveAvailabilityOnly($data));
    }

    protected function getAvailabilityActionDisabledReason(): ?string
    {
        try {
            $state = method_exists($this->form, 'getRawState')
                ? $this->form->getRawState()
                : ($this->form ? $this->form->getState() : []);

            if (! $this->record && (empty($state['first_name'] ?? null) || empty($state['email'] ?? null))) {
                return 'Fill all required fields to
                 availability';
            }
        } catch (\Throwable $e) {
            // Be conservative: if we cannot read state, keep the action disabled.
            return 'Fill all required fields to availability';
        }

        return null;
    }

    protected function mergeArraysDeep(array $a, array $b): array
    {
        foreach ($b as $key => $value) {
            // Special handling for slots arrays to avoid duplicates during merging
            if (is_string($key) && str_starts_with($key, 'slots_') && is_array($value) && isset($a[$key]) && is_array($a[$key])) {
                $mergedSlots = $a[$key];
                foreach ($value as $vSlot) {
                    if (! is_array($vSlot)) {
                        continue;
                    }

                    $vStart = $this->normalizeTime($vSlot['start_time'] ?? $vSlot['start'] ?? null);
                    $vEnd = $this->normalizeTime($vSlot['end_time'] ?? $vSlot['end'] ?? null);
                    $vDate = empty($vSlot['is_recurring']) ? ($vSlot['date'] ?? null) : null;
                    if ($vDate) {
                        $vDate = \Carbon\Carbon::parse($vDate)->format('Y-m-d');
                    }

                    $vKey = ($vDate ?? 'null') . '_' . ($vStart ?? 'x') . '_' . ($vEnd ?? 'y');

                    $found = false;
                    foreach ($mergedSlots as &$mSlot) {
                        $mStart = $this->normalizeTime($mSlot['start_time'] ?? $mSlot['start'] ?? null);
                        $mEnd = $this->normalizeTime($mSlot['end_time'] ?? $mSlot['end'] ?? null);
                        $mDate = empty($mSlot['is_recurring']) ? ($mSlot['date'] ?? null) : null;
                        if ($mDate) {
                            $mDate = \Carbon\Carbon::parse($mDate)->format('Y-m-d');
                        }

                        $mKey = ($mDate ?? 'null') . '_' . ($mStart ?? 'x') . '_' . ($mEnd ?? 'y');

                        if ($vKey === $mKey) {
                            $mSlot = array_merge($mSlot, $vSlot);
                            $found = true;
                            break;
                        }
                    }
                    if (! $found) {
                        $mergedSlots[] = $vSlot;
                    }
                }
                $a[$key] = $mergedSlots;
            } elseif (is_array($value) && isset($a[$key]) && is_array($a[$key])) {
                $a[$key] = $this->mergeArraysDeep($a[$key], $value);
            } else {
                $a[$key] = $value;
            }
        }

        return $a;
    }

    private function getValidationService(): DoctorAvailabilityValidationService
    {
        return app(DoctorAvailabilityValidationService::class);
    }

    private function normalizeTime($time): ?string
    {
        return $this->getValidationService()->normalizeTime($time);
    }

    private function normalizeConsultationType($value): string
    {
        return strtolower((string) $value) === 'video' ? 'video' : 'in-person';
    }

    private function normalizeOpdType($value, string $consultationType): ?string
    {
        if ($consultationType === 'video') {
            return null;
        }

        $normalized = strtolower(trim((string) ($value ?? 'general')));
        return in_array($normalized, ['general', 'private'], true) ? $normalized : 'general';
    }

    /**
     * Check if an availability slot already exists
     * Matches the unique constraint: ['doctor_id', 'date', 'start_time', 'end_time']
     * Note: day_of_week is NOT part of the unique constraint, so we don't check it
     */
    private function slotExists(
        string $doctorId,
        ?string $date,
        string $startTime,
        string $endTime,
        ?string $excludeId = null,
        ?string $dayOfWeek = null,
        ?string $recurringStartDate = null,
        ?string $recurringEndDate = null
    ): bool {
        return $this->getValidationService()->slotExistsInDatabase(
            $doctorId,
            $date,
            $startTime,
            $endTime,
            $excludeId,
            $dayOfWeek,
            $recurringStartDate,
            $recurringEndDate
        );
    }
    protected function getAvailabilitySlotsData(): array
    {
        $data = [];
        if ($this->record && $this->record->exists) {
            $doctor = $this->record;
            $dayLabels = DayOfWeek::labels();

            foreach (array_keys($dayLabels) as $day) {
                $dayKeyLower = strtolower($day);
                $targetDow = \Carbon\Carbon::parse($dayKeyLower)->dayOfWeek;

                $data["slots_{$dayKeyLower}"] = $doctor->availabilities()
                    ->where(function ($query) use ($dayKeyLower, $targetDow) {
                        $query->where('day_of_week', $dayKeyLower)
                            ->orWhereRaw('(is_recurring = 1 AND DAYOFWEEK(recurring_start_date) = ?)', [$targetDow + 1])
                            ->orWhereRaw('(is_recurring = 0 AND date IS NOT NULL AND DAYOFWEEK(date) = ?)', [$targetDow + 1]);
                    })
                    ->get()
                    ->map(fn($slot) => [
                        'id' => $slot->id,
                        'day_of_week' => $slot->day_of_week,
                        'date' => $slot->date ? \Carbon\Carbon::parse($slot->date)->format('Y-m-d') : null,
                        'start_time' => \Carbon\Carbon::parse($slot->start_time)->format('H:i'),
                        'end_time' => \Carbon\Carbon::parse($slot->end_time)->format('H:i'),
                        'capacity' => $slot->capacity,
                        'consultation_type' => $slot->consultation_type,
                        'opd_type' => $slot->consultation_type === 'video' ? null : ($slot->opd_type ?? 'general'),
                        'doctor_room' => $slot->doctor_room,
                        'consultation_fee' => $slot->consultation_fee ?? 0,
                        'is_available' => (bool) ($slot->is_available ?? true),
                        'is_recurring' => (bool) $slot->is_recurring,
                        'recurring_start_date' => $slot->recurring_start_date
                            ? \Carbon\Carbon::parse($slot->recurring_start_date)->format('Y-m-d')
                            : null,
                        'recurring_end_date' => $slot->recurring_end_date
                            ? \Carbon\Carbon::parse($slot->recurring_end_date)->format('Y-m-d')
                            : null,
                        'is_editing' => false,
                        'recurring_months' => $slot->recurring_start_date && $slot->recurring_end_date
                            ? \Carbon\Carbon::parse($slot->recurring_start_date)
                            ->diffInMonths(\Carbon\Carbon::parse($slot->recurring_end_date))
                            : null,
                    ])
                    ->toArray();
            }
        }
        return $data;
    }

    private function calculateRecurringStartDate(string $day, $startTime): string
    {
        $now = \Carbon\Carbon::now();
        $selectedDow = \Carbon\Carbon::parse($day)->dayOfWeek; // 0=Sun ... 6=Sat
        $todayDow = $now->dayOfWeek;

        $startTimeFormatted = \Carbon\Carbon::parse($startTime)->format('H:i');
        $todayStartDateTime = \Carbon\Carbon::parse($now->format('Y-m-d') . ' ' . $startTimeFormatted);

        // Case 1: Same day
        if ($todayDow === $selectedDow) {

            // Time NOT passed → today
            if ($now->lt($todayStartDateTime)) {
                return $now->format('Y-m-d');
            }

            // Time passed → next week
            return $now->copy()->addWeek()->next($selectedDow)->format('Y-m-d');
        }

        // Case 2: Different day → calculate exact next occurrence

        $daysToAdd = ($selectedDow - $todayDow + 7) % 7;

        // If same day fallback (should not happen but safe)
        if ($daysToAdd === 0) {
            $daysToAdd = 7;
        }

        return $now->copy()->addDays($daysToAdd)->format('Y-m-d');
    }

    /**
     * Persist availability slots to the database for a given doctor.
     * Only saves/updates slots that have actually changed.
     */
    public function persistAvailabilitySlots($doctor, array $data, bool $isNewDoctor = false, bool $notify = true): array
    {
        $results = [
            'totalSaved' => 0,
            'totalUpdated' => 0,
            'totalSkipped' => 0,
            'errors' => [],
            'hasErrors' => false,
        ];

        if (! $doctor) {
            return $results;
        }

        $doctorId = $doctor->id;
        $totalSaved = 0;
        $totalUpdated = 0;
        $totalSkipped = 0;
        $errors = [];
        $hasErrors = false;

        // Track processed slot IDs and composite keys to avoid duplicates
        $processedSlotIds = [];
        $processedKeys = [];
        $savedSlots = [];
        $updatedSlots = [];

        // Build a map of existing slots from DB for comparison
        $existingSlotsMap = [];
        $existingSlots = DoctorAvailability::where('doctor_id', $doctorId)->get();
        foreach ($existingSlots as $slot) {
            $key = ($slot->date ?? 'null') . '_' . $slot->start_time . '_' . $slot->end_time;
            $existingSlotsMap[$key] = $slot;
        }

        try {
            DB::transaction(function () use ($doctorId, $data, &$totalSaved, &$totalUpdated, &$totalSkipped, &$errors, &$hasErrors, &$processedSlotIds, &$processedKeys, &$savedSlots, &$updatedSlots) {
                $dayLabels = DayOfWeek::labels();

                foreach (array_keys($dayLabels) as $day) {
                    $dayKeyLower = strtolower($day);
                    $slots = $data["slots_{$day}"] ?? $data["slots_{$dayKeyLower}"] ?? [];

                    if (! is_array($slots)) {
                        continue;
                    }

                    foreach ($slots as $slot) {
                        if (! is_array($slot)) {
                            continue;
                        }

                        $slotId = $slot['id'] ?? null;

                        // Skip if we've already processed this slot ID
                        if ($slotId && isset($processedSlotIds[$slotId])) {
                            continue;
                        }

                        $start = $this->normalizeTime($slot['start_time'] ?? $slot['start'] ?? null);
                        $end = $this->normalizeTime($slot['end_time'] ?? $slot['end'] ?? null);

                        if (! $start || ! $end) {
                            continue;
                        }

                        $isRecurring = (bool) ($slot['is_recurring'] ?? false);
                        $date = $isRecurring ? null : ($slot['date'] ?? null);

                        // Normalize date format for saving
                        $normalizedDate = null;
                        if ($date) {
                            try {
                                $normalizedDate = \Carbon\Carbon::parse($date)->format('Y-m-d');
                            } catch (\Exception $e) {
                                $normalizedDate = null;
                            }
                        }

                        $normalizedRecurringStart = $isRecurring
                            ? $this->getValidationService()->normalizeDate($slot['recurring_start_date'] ?? null)
                            : null;
                        $normalizedRecurringEnd = $isRecurring
                            ? $this->getValidationService()->normalizeDate($slot['recurring_end_date'] ?? null)
                            : null;

                        $slotKey = implode('_', [
                            $dayKeyLower,
                            $normalizedDate ?? 'null',
                            $start,
                            $end,
                            $normalizedRecurringStart ?? 'null',
                            $normalizedRecurringEnd ?? 'null',
                        ]);

                        if (isset($processedKeys[$slotKey])) {
                            $totalSkipped++;
                            continue;
                        }
                        $processedKeys[$slotKey] = true;

                        if (! $slotId && $this->slotExists($doctorId, $normalizedDate, $start, $end, null, $dayKeyLower, $normalizedRecurringStart, $normalizedRecurringEnd)) {
                            $totalSkipped++;
                            continue;
                        }

                        // Check for overlaps in DB
                        $overlaps = $this->getValidationService()->slotOverlapsInDatabase(
                            $doctorId,
                            $normalizedDate,
                            $start,
                            $end,
                            $slotId,
                            $dayKeyLower,
                            $normalizedRecurringStart,
                            $normalizedRecurringEnd
                        );
                        if (!empty($overlaps)) {
                            $overlap = $overlaps[0];
                            $dayName = ucfirst($dayKeyLower);
                            $dateStr = $normalizedDate ? \Carbon\Carbon::parse($normalizedDate)->format('M d, Y') : 'recurring';
                            $errors[] = "Time overlap: {$dayName} {$start}-{$end} clashes with existing {$overlap['start_time']}-{$overlap['end_time']} on {$dateStr}";
                            $totalSkipped++;
                            $hasErrors = true;
                            continue;
                        }

                        // Ensure time is in H:i:00 format (forcing zero seconds)
                        $startTimeFormatted = \Carbon\Carbon::parse($start)->format('H:i:00');
                        $endTimeFormatted = \Carbon\Carbon::parse($end)->format('H:i:00');

                        // Only save required fields for availability
                        $consultationType = $this->normalizeConsultationType($slot['consultation_type'] ?? 'in-person');
                        $normalizedOpdType = $this->normalizeOpdType($slot['opd_type'] ?? null, $consultationType);
                        $availabilityData = [
                            'doctor_id' => $doctorId,
                            'day_of_week' => $dayKeyLower,
                            'start_time' => $startTimeFormatted,
                            'end_time' => $endTimeFormatted,
                            'capacity' => (int) ($slot['capacity'] ?? 1),
                            'consultation_type' => $consultationType,
                            'opd_type' => $normalizedOpdType,
                            'consultation_fee' => (float) ($slot['consultation_fee'] ?? 0),
                            'doctor_room' => $slot['doctor_room'] ?? null,
                            'is_recurring' => $isRecurring,
                            'is_available' => (bool) ($slot['is_available'] ?? true),
                        ];

                        if ($isRecurring) {
                            // ✅ START DATE
                            $startDateValue = $slot['recurring_start_date'] ?? null;
                            if (empty($startDateValue)) {
                                $availabilityData['recurring_start_date'] = $this->calculateRecurringStartDate($dayKeyLower, $start);
                            } else {
                                $availabilityData['recurring_start_date'] = $this->getValidationService()->normalizeDate($startDateValue);
                            }

                            $months = isset($slot['recurring_months']) && (int) $slot['recurring_months'] > 0
                                ? (int) $slot['recurring_months']
                                : 3;

                            // ✅ END DATE LOGIC
                            $endDateValue = $slot['recurring_end_date'] ?? null;
                            if (!empty($endDateValue)) {
                                $availabilityData['recurring_end_date'] = $this->getValidationService()->normalizeDate($endDateValue);
                            } else {
                                $availabilityData['recurring_end_date'] =
                                    \Carbon\Carbon::parse($availabilityData['recurring_start_date'])
                                    ->addMonths($months)
                                    ->format('Y-m-d');
                            }

                            $availabilityData['recurring_months'] = $months;
                            $availabilityData['date'] = null;
                        } else {
                            $availabilityData['date'] = $normalizedDate;
                            $availabilityData['recurring_start_date'] = null;
                            $availabilityData['recurring_end_date'] = null;
                            $availabilityData['recurring_months'] = 3;
                        }

                        // If updating existing slot
                        if ($slotId) {
                            // Check if slot still exists and belongs to this doctor
                            $existingSlot = DoctorAvailability::where('id', $slotId)
                                ->where('doctor_id', $doctorId)
                                ->first();

                            if ($existingSlot) {
                                if ($existingSlot->created_at && $existingSlot->created_at->diffInSeconds(now()) < 15) {
                                    if ($slotId) {
                                        $processedSlotIds[$slotId] = true;
                                    }
                                    continue;
                                }

                                // Normalize existing slot times for comparison
                                $existingStart = $this->normalizeTime($existingSlot->start_time);
                                $existingEnd = $this->normalizeTime($existingSlot->end_time);
                                $existingDate = $existingSlot->date ? \Carbon\Carbon::parse($existingSlot->date)->format('Y-m-d') : null;

                                // Check changes
                                $dateChanged = $existingDate !== $normalizedDate;
                                $startChanged = $existingStart !== $start;
                                $endChanged = $existingEnd !== $end;
                                $capacityChanged = (int) ($existingSlot->capacity ?? 1) !== (int) ($slot['capacity'] ?? 1);
                                $feeChanged = (float) ($existingSlot->consultation_fee ?? 0) !== (float) ($slot['consultation_fee'] ?? 0);
                                $typeChanged = ($existingSlot->consultation_type ?? 'in-person') !== $consultationType;
                                $opdChanged = (($existingSlot->consultation_type ?? 'in-person') === 'video' ? null : ($existingSlot->opd_type ?? 'general')) !== $normalizedOpdType;
                                $availableChanged = (bool) ($existingSlot->is_available ?? true) !== (bool) ($slot['is_available'] ?? true);
                                $recurringChanged = (bool) ($existingSlot->is_recurring ?? false) !== $isRecurring;

                                $existingRoom = empty(trim($existingSlot->doctor_room ?? '')) ? null : trim($existingSlot->doctor_room);
                                $newRoom = empty(trim($slot['doctor_room'] ?? '')) ? null : trim($slot['doctor_room']);
                                $roomChanged = $existingRoom !== $newRoom;

                                $hasChanges = $dateChanged || $startChanged || $endChanged || $capacityChanged ||
                                    $feeChanged || $typeChanged || $opdChanged || $availableChanged || $recurringChanged || $roomChanged;

                                if (! $hasChanges) {
                                    if ($slotId) {
                                        $processedSlotIds[$slotId] = true;
                                    }
                                    continue;
                                }

                                if ($dateChanged || $startChanged || $endChanged) {
                                    if ($this->slotExists($doctorId, $normalizedDate, $start, $end, $slotId, $dayKeyLower, $normalizedRecurringStart, $normalizedRecurringEnd)) {
                                        $totalSkipped++;
                                        continue;
                                    }
                                }

                                try {
                                    $existingSlot->update($availabilityData);
                                    $updatedSlots[] = $availabilityData;
                                    $totalUpdated++;
                                    if ($slotId) {
                                        $processedSlotIds[$slotId] = true;
                                    }
                                } catch (UniqueConstraintViolationException $e) {
                                    $dayName = ucfirst($dayKeyLower);
                                    $dateStr = $normalizedDate ? \Carbon\Carbon::parse($normalizedDate)->format('M d, Y') : 'recurring';
                                    $errors[] = "Cannot update slot: {$dayName} {$start}-{$end} on {$dateStr} - duplicate constraint violation";
                                    $totalSkipped++;
                                    $hasErrors = true;
                                }
                            } else {
                                $slotId = null;
                            }
                        }

                        // Creating new slot
                        if (! $slotId) {
                            if ($this->slotExists($doctorId, $normalizedDate, $start, $end, null, $dayKeyLower, $normalizedRecurringStart, $normalizedRecurringEnd)) {
                                $totalSkipped++;
                                continue;
                            }

                            try {
                                $newSlot = DoctorAvailability::create($availabilityData);
                                $savedSlots[] = $availabilityData;
                                $totalSaved++;
                                if (isset($newSlot->id)) {
                                    $processedSlotIds[$newSlot->id] = true;
                                }
                            } catch (UniqueConstraintViolationException $e) {
                                $totalSkipped++;
                            }
                        }
                    }
                }

                // Quick Add
                $tempDay = strtolower($data['temp_day'] ?? '');
                $tempStart = $this->normalizeTime($data['temp_start'] ?? null);
                $tempEnd = $this->normalizeTime($data['temp_end'] ?? null);

                if ($tempDay && $tempStart && $tempEnd) {
                    $isRec = (bool) ($data['temp_rec'] ?? false);
                    $tempDate = $isRec ? null : ($data['temp_date'] ?? null);
                    $normalizedTempDate = $tempDate ? \Carbon\Carbon::parse($tempDate)->format('Y-m-d') : null;

                    $tempKey = ($normalizedTempDate ?? 'null') . '_' . $tempStart . '_' . $tempEnd;
                    if (isset($processedKeys[$tempKey])) {
                        return;
                    }

                    if ($this->slotExists($doctorId, $normalizedTempDate, $tempStart, $tempEnd, null, $tempDay)) {
                        $totalSkipped++;
                    } else {
                        $tempConsultationType = $this->normalizeConsultationType($data['temp_cons'] ?? 'in-person');
                        $pendingData = [
                            'doctor_id' => $doctorId,
                            'day_of_week' => $tempDay,
                            'start_time' => \Carbon\Carbon::parse($tempStart)->format('H:i:00'),
                            'end_time' => \Carbon\Carbon::parse($tempEnd)->format('H:i:00'),
                            'capacity' => (int) ($data['temp_cap'] ?? 1),
                            'consultation_type' => $tempConsultationType,
                            'opd_type' => $this->normalizeOpdType($data['temp_opd'] ?? null, $tempConsultationType),
                            'consultation_fee' => (float) ($data['temp_fee'] ?? 0),
                            'doctor_room' => $data['temp_room'] ?? null,
                            'is_recurring' => $isRec,
                            'is_available' => (bool) ($data['temp_active'] ?? true),
                        ];

                        if ($isRec) {
                            $pendingData['recurring_start_date'] = $this->calculateRecurringStartDate($tempDay, $tempStart);
                            $months = (int) ($data['temp_months'] ?? 3);
                            $pendingData['recurring_end_date'] = now()->addMonths($months)->format('Y-m-d');
                            $pendingData['date'] = null;
                        } else {
                            $pendingData['date'] = $normalizedTempDate;
                        }

                        try {
                            DoctorAvailability::create($pendingData);
                            $savedSlots[] = $pendingData;
                            $totalSaved++;
                        } catch (UniqueConstraintViolationException $e) {
                            $totalSkipped++;
                        }
                    }
                }
            });

            if ($notify) {
                if ($hasErrors && ! empty($errors)) {
                    Notification::make()
                        ->title('Some Slots Could Not Be Saved')
                        ->body(implode("\n", array_slice($errors, 0, 5)) . (count($errors) > 5 ? "\n...and " . (count($errors) - 5) . ' more' : ''))
                        ->danger()
                        ->persistent()
                        ->send();
                }

                if ($totalSaved > 0 || $totalUpdated > 0) {
                    if ($totalSaved > 0) {
                        \App\Services\NotificationService::notifyAvailabilityCreated($doctor, $savedSlots);
                    }
                    if ($totalUpdated > 0) {
                        \App\Services\NotificationService::notifyAvailabilityUpdated($doctor, $updatedSlots);
                    }

                    if ($isNewDoctor && $totalSaved > 0) {
                        Notification::make()->title('Slots Added')->body("Added {$totalSaved} new slots.")->success()->send();
                    } elseif ($totalSaved === 1 && $totalUpdated === 0) {
                        Notification::make()->title('Slot Created')->success()->send();
                    } elseif ($totalUpdated === 1 && $totalSaved === 0) {
                        Notification::make()->title('Update Successful')->success()->send();
                    } elseif ($totalSaved > 0 && $totalUpdated > 0) {
                        Notification::make()->title('Availability Saved')->body("{$totalSaved} created, {$totalUpdated} updated.")->success()->send();
                    } else {
                         Notification::make()->title('Update Successful')->success()->send();
                    }
                } elseif ($hasErrors && $totalSkipped > 0) {
                    Notification::make()->title('No Changes Saved')->body('All slots were duplicates.')->danger()->send();
                } elseif (! $hasErrors && $totalSkipped > 0) {
                    Notification::make()->title('No New Slots Added')->body('All provided slots already existed and were skipped.')->warning()->send();
                }
            }
        } catch (\Throwable $e) {
            if ($notify) {
                Notification::make()->title('Error')->body($e->getMessage())->danger()->send();
            }
            $hasErrors = true;
            $errors[] = $e->getMessage();
        }

        return [
            'totalSaved' => $totalSaved,
            'totalUpdated' => $totalUpdated,
            'totalSkipped' => $totalSkipped,
            'errors' => $errors,
            'hasErrors' => $hasErrors,
        ];
    }

    /**
     * Validate slots for duplicates before saving
     * Checks based on unique constraint: ['doctor_id', 'date', 'start_time', 'end_time']
     */
    private function validateSlotsForDuplicates($doctorId, array $data): array
    {
        return $this->getValidationService()->validateAllSlots($doctorId, $data);
    }

    /**
     * Save availability data - handles both create and edit modes
     * Only validates slots that have actually changed
     */
    protected function saveAvailabilityOnly(array $data): void
    {
        try {
            // Build merged state from form
            $currentState = method_exists($this->form, 'getRawState') ? $this->form->getRawState() : $this->form->getState();
            $merged = $this->mergeArraysDeep($currentState ?? [], $data ?? []);

            // If the page has staged availability (from create flow), merge it too
            if (property_exists($this, 'pendingAvailabilityData') && ! empty($this->pendingAvailabilityData)) {
                $merged = $this->mergeArraysDeep($merged, $this->pendingAvailabilityData ?? []);
            }

            $this->form->fill($merged);

            // If record exists (EDIT mode), validate and persist immediately to DB
            if ($this->record?->exists) {
                $doctorId = $this->record->id;

                // Build a map of existing slots from DB for comparison to detect what changed
                $dbSlots = [];
                $existingDbSlots = DoctorAvailability::where('doctor_id', $doctorId)->get();
                foreach ($existingDbSlots as $slot) {
                    $key = ($slot->date ?? 'null') . '_' . $slot->start_time . '_' . $slot->end_time;
                    $dbSlots[$key] = $slot;
                }

                // Filter the merged data to only include slots that have changed (especially time/date changes)
                $changedData = $merged;
                $dayLabels = DayOfWeek::labels();
                $hasTimeOrDateChanges = false;

                foreach (array_keys($dayLabels) as $day) {
                    $dayKeyLower = strtolower($day);
                    $formSlots = $merged["slots_{$dayKeyLower}"] ?? [];

                    if (! is_array($formSlots)) {
                        continue;
                    }

                    $changedSlots = [];
                    foreach ($formSlots as $slot) {
                        if (! is_array($slot)) {
                            $changedSlots[] = $slot;

                            continue;
                        }

                        $slotId = $slot['id'] ?? null;

                        // If no ID, it's a new slot - include it and mark that we have time/date changes
                        if (! $slotId) {
                            $changedSlots[] = $slot;
                            $hasTimeOrDateChanges = true;

                            continue;
                        }

                        // If has ID, check if it has changed
                        $existingSlot = DoctorAvailability::find($slotId);
                        if (! $existingSlot) {
                            // Slot doesn't exist in DB, might have been deleted - include it
                            $changedSlots[] = $slot;
                            $hasTimeOrDateChanges = true;

                            continue;
                        }

                        // Compare fields to detect changes
                        $start = $this->normalizeTime($slot['start_time'] ?? null);
                        $end = $this->normalizeTime($slot['end_time'] ?? null);
                        $existingStart = $this->normalizeTime($existingSlot->start_time);
                        $existingEnd = $this->normalizeTime($existingSlot->end_time);
                        $date = ($slot['is_recurring'] ?? false) ? null : ($slot['date'] ?? null);
                        if ($date) {
                            $date = \Carbon\Carbon::parse($date)->format('Y-m-d');
                        }
                        $existingDate = $existingSlot->date ? \Carbon\Carbon::parse($existingSlot->date)->format('Y-m-d') : null;

                        // Check if time or date has changed
                        $timeOrDateChanged = $start !== $existingStart || $end !== $existingEnd || $date !== $existingDate;

                        // Check if other fields have changed strictly

                        $existingRoom = empty(trim($existingSlot->doctor_room ?? '')) ? null : trim($existingSlot->doctor_room);
                        $newRoom = empty(trim($slot['doctor_room'] ?? '')) ? null : trim($slot['doctor_room']);

                        $normalizedConsultationType = $this->normalizeConsultationType($slot['consultation_type'] ?? 'in-person');
                        $normalizedOpdType = $this->normalizeOpdType($slot['opd_type'] ?? null, $normalizedConsultationType);
                        $existingNormalizedOpdType = ($existingSlot->consultation_type ?? 'in-person') === 'video'
                            ? null
                            : ($existingSlot->opd_type ?? 'general');

                        $otherFieldsChanged =
                            ((int) ($slot['capacity'] ?? 1)) !== ((int) ($existingSlot->capacity ?? 1)) ||
                            (float) ($slot['consultation_fee'] ?? 0) !== (float) ($existingSlot->consultation_fee ?? 0) ||
                            $normalizedConsultationType !== ($existingSlot->consultation_type ?? 'in-person') ||
                            $normalizedOpdType !== $existingNormalizedOpdType ||
                            (bool) ($slot['is_available'] ?? true) !== (bool) ($existingSlot->is_available ?? true) ||
                            (bool) ($slot['is_recurring'] ?? false) !== (bool) ($existingSlot->is_recurring ?? false) ||
                            $newRoom !== $existingRoom;

                        // Only validate slots with time/date changes; always include them if ANY field changed
                        if ($timeOrDateChanged) {
                            $changedSlots[] = $slot;
                            $hasTimeOrDateChanges = true;
                        } elseif ($otherFieldsChanged) {
                            // Has other changes but not time/date - include for persistence but skip validation
                            $changedSlots[] = $slot;
                        }
                        // If no changes at all, don't include in changedSlots
                    }

                    $changedData["slots_{$dayKeyLower}"] = $changedSlots;
                }

                // Only validate if there are time or date changes (which could cause overlaps)
                // Skip validation for updates that only change other fields like fee, capacity, etc.
                if ($hasTimeOrDateChanges) {
                    $validationErrors = $this->validateSlotsForDuplicates($doctorId, $changedData);

                    if (! empty($validationErrors)) {
                        // Show errors and prevent saving
                        Notification::make()
                            ->title('Cannot Save Availability')
                            ->body(implode("\n", array_slice($validationErrors, 0, 5)) . (count($validationErrors) > 5 ? "\n...and " . (count($validationErrors) - 5) . ' more issue(s) found.' : ''))
                            ->danger()
                            ->persistent()
                            ->send();

                        // Don't close modal, let user fix the issues
                        return;
                    }
                }

                // Check if doctor previously had no active availability (before saving)
                $credentialsService = app(\App\Services\DoctorCredentialsService::class);
                $hadNoAvailabilityBefore = ! $credentialsService->hasActiveAvailability($this->record);

                // No duplicates found, proceed with saving (use full merged data for persistence)
                $this->persistAvailabilitySlots($this->record, $merged);

                // Refresh to get latest availability data
                $this->record->refresh();

                // Check if doctor now has active availability (after saving)
                $hasAvailabilityNow = $credentialsService->hasActiveAvailability($this->record);

                // If doctor went from no availability to having availability, trigger credential modal
                if ($hadNoAvailabilityBefore && $hasAvailabilityNow && $this->record->user) {
                    // Set session data
                    session([
                        'availability_just_added' => true,
                        'availability_doctor_id' => $this->record->id,
                    ]);

                    // Generate password and store in session
                    $newPassword = \Illuminate\Support\Str::random(12);
                    session(['availability_new_password' => $newPassword]);

                    // If this is EditDoctor page, directly show the modal
                    // Check if properties exist (EditDoctor has these, CreateDoctor doesn't)
                    if (property_exists($this, 'showCredentialModal') && property_exists($this, 'modalType')) {
                        $this->showCredentialModal = true;
                        $this->modalType = 'availability';
                    }
                }
            } else {
                // CREATE mode - stage the data for later persistence
                // Note: We can't validate against DB yet since doctor doesn't exist
                // But we can validate for duplicates within the form data
                $validationErrors = $this->validateSlotsForDuplicates(null, $merged);

                if (! empty($validationErrors)) {
                    Notification::make()
                        ->title('Cannot Stage Availability')
                        ->body(implode("\n", array_slice($validationErrors, 0, 5)) . (count($validationErrors) > 5 ? "\n...and " . (count($validationErrors) - 5) . ' more issue(s) found.' : ''))
                        ->danger()
                        ->persistent()
                        ->send();

                    // Don't close modal, let user fix the issues
                    return;
                }

                if (property_exists($this, 'pendingAvailabilityData')) {
                    $this->pendingAvailabilityData = $this->mergeArraysDeep($this->pendingAvailabilityData ?? [], $merged);

                    try {
                        $raw = method_exists($this->form, 'getRawState') ? $this->form->getRawState() : $this->form->getState();
                        $this->pendingAvailabilityData = $this->mergeArraysDeep($this->pendingAvailabilityData, $raw ?? []);
                    } catch (\Throwable $e) {
                        // Silent fail - staging is best-effort
                    }
                }

                Notification::make()
                    ->title('Availability Staged')
                    ->body('Configuration applied. Changes will be saved when you create the doctor profile.')
                    ->success()
                    ->send();
            }

            // Force close the modal only if save was successful
            $this->dispatch('close-modal', id: 'manageAvailability');

            // If availability was just added and modal should show, dispatch event after slideOver closes
            if (property_exists($this, 'showCredentialModal') && $this->showCredentialModal) {
                // Dispatch event to show credential modal after a short delay (to let slideOver close)
                $this->dispatch('show-credential-modal');
            }
        } catch (\Throwable $e) {
            Notification::make()
                ->title('Error')
                ->body('Failed to save availability: ' . $e->getMessage())
                ->danger()
                ->send();
        }
    }
}
