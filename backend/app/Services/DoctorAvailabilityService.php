<?php

namespace App\Services;

use App\Models\DoctorAvailability;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Filament\Notifications\Notification;
use App\Enums\{DayOfWeek};
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\UniqueConstraintViolationException;

class DoctorAvailabilityService
{
    /**
     * Expand recurring availabilities into discrete slots for a given window.
     */
    public function expandSlots(iterable $availabilities, Carbon $startDate, Carbon $endDate): Collection
    {
        $allSlots = collect();
        $now = Carbon::now();

        foreach ($availabilities as $slot) {
            if ($slot->is_recurring) {
                $recurringStart = $slot->recurring_start_date
                    ? Carbon::parse($slot->recurring_start_date)->startOfDay()
                    : $startDate->copy();
                $recurringEnd = $slot->recurring_end_date
                    ? Carbon::parse($slot->recurring_end_date)->endOfDay()
                    : $endDate->copy();

                $rangeStart = $startDate->greaterThan($recurringStart) ? $startDate->copy() : $recurringStart;
                $rangeEnd = $recurringEnd->lessThan($endDate) ? $recurringEnd : $endDate;

                // Use the weekday of the recurring start date as the source of truth
                $dow = $recurringStart->dayOfWeek;

                $current = $rangeStart->copy();

                // Move to the first occurrence of this day of week within our range
                if ($current->dayOfWeek !== $dow) {
                    $current->next($dow);
                }

                while ($current->lte($rangeEnd)) {
                    $slotCopy = clone $slot;
                    $slotCopy->date = $current->toDateString();
                    $allSlots->push($slotCopy);
                    $current->addWeek();
                }
            } else {
                $dateObj = $slot->date ? Carbon::parse($slot->date) : null;
                if ($dateObj && $dateObj->between($startDate, $endDate)) {
                    $slot->date = $dateObj->toDateString();
                    $allSlots->push($slot);
                }
            }
        }

        return $allSlots->filter(function ($slot) use ($now) {
            $timeString = $slot->start_time instanceof Carbon
                ? $slot->start_time->format('H:i:s')
                : $slot->start_time;

            $slotDateTime = Carbon::parse($slot->date . ' ' . $timeString);
            return $slotDateTime->greaterThan($now);
        })
            ->unique(function ($slot) {
                $startTime = $slot->start_time instanceof Carbon ? $slot->start_time->format('H:i') : date('H:i', strtotime($slot->start_time));
                $endTime = $slot->end_time instanceof Carbon ? $slot->end_time->format('H:i') : date('H:i', strtotime($slot->end_time));
                return $slot->date . '|' . $startTime . '|' . $endTime . '|' . $slot->consultation_type;
            })
            ->sortBy([
                ['date', 'asc'],
                ['start_time', 'asc'],
            ]);
    }

    /**
     * Group slots by date for API response.
     */
    public function groupSlotsByDate(Collection $slots): Collection
    {
        return $slots->groupBy('date')->map(fn($groupSlots, $date) => [
            'date' => $date,
            'slots' => $groupSlots->values(),
        ])->values();
    }
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
            \Illuminate\Support\Facades\DB::transaction(function () use ($doctorId, $data, &$totalSaved, &$totalUpdated, &$totalSkipped, &$errors, &$hasErrors, &$processedSlotIds, &$processedKeys, &$savedSlots, &$updatedSlots) {
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

                        // Track this unique slot key to avoid duplicates within this same request
                        $slotKey = $dayKeyLower . '_' . ($normalizedDate ?? 'null') . '_' . $start . '_' . $end;

                        if (isset($processedKeys[$slotKey])) {
                            $totalSkipped++;
                            $hasErrors = true;
                             $dayName = ucfirst($dayKeyLower);
                            $dateStr = $normalizedDate ? \Carbon\Carbon::parse($normalizedDate)->format('M d, Y') : 'recurring';
                            $errors[] = "Duplicate in file: {$dayName} {$start}-{$end} on {$dateStr}";
                            continue;
                        }
                        $processedKeys[$slotKey] = true;

                        // Check for overlaps in DB (In addition to exact duplicates)
                        $overlaps = $this->getValidationService()->slotOverlapsInDatabase($doctorId, $normalizedDate, $start, $end, $slotId, $dayKeyLower);
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
                        $availabilityData = [
                            'doctor_id' => $doctorId,
                            'date' => $normalizedDate,
                            'day_of_week' => $dayKeyLower,
                            'start_time' => $startTimeFormatted,
                            'end_time' => $endTimeFormatted,
                            'capacity' => (int) ($slot['capacity'] ?? 1),
                            'consultation_type' => $slot['consultation_type'] ?? 'in-person',
                            'opd_type' => $slot['opd_type'] ?? 'general',
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
                                : 3; // Default to 3 months if not specified or invalid

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
                            $availabilityData['recurring_months'] = null;
                        }

                        // If updating existing slot
                        if ($slotId) {
                            // Check if slot still exists and belongs to this doctor
                            $existingSlot = DoctorAvailability::where('id', $slotId)
                                ->where('doctor_id', $doctorId)
                                ->first();

                            if ($existingSlot) {
                                // If the slot was just created via the "Add Consultation Slot" button moments ago,
                                // we shouldn't attempt to update it immediately, which avoids false "Updated" notifications.
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

                                // Check if the unique fields (date, start_time, end_time) have changed
                                $dateChanged = $existingDate !== $normalizedDate;
                                $startChanged = $existingStart !== $start;
                                $endChanged = $existingEnd !== $end;

                                // Check if any other fields have changed strictly
                                $capacityChanged = (int) ($existingSlot->capacity ?? 1) !== (int) ($slot['capacity'] ?? 1);
                                $feeChanged = (float) ($existingSlot->consultation_fee ?? 0) !== (float) ($slot['consultation_fee'] ?? 0);
                                $typeChanged = ($existingSlot->consultation_type ?? 'in-person') !== ($slot['consultation_type'] ?? 'in-person');
                                $opdChanged = ($existingSlot->opd_type ?? 'general') !== ($slot['opd_type'] ?? 'general');
                                $availableChanged = (bool) ($existingSlot->is_available ?? true) !== (bool) ($slot['is_available'] ?? true);
                                $recurringChanged = (bool) ($existingSlot->is_recurring ?? false) !== $isRecurring;

                                $existingRoom = empty(trim($existingSlot->doctor_room ?? '')) ? null : trim($existingSlot->doctor_room);
                                $newRoom = empty(trim($slot['doctor_room'] ?? '')) ? null : trim($slot['doctor_room']);
                                $roomChanged = $existingRoom !== $newRoom;

                                // Only update if something actually changed
                                $hasChanges = $dateChanged || $startChanged || $endChanged || $capacityChanged ||
                                    $feeChanged || $typeChanged || $opdChanged || $availableChanged || $recurringChanged || $roomChanged;

                                if (! $hasChanges) {
                                    // No changes, skip this slot
                                    if ($slotId) {
                                        $processedSlotIds[$slotId] = true;
                                    }

                                    continue;
                                }

                                // Only check for duplicates if the unique fields have changed
                                // This prevents "overlap with self" errors when updating non-time fields
                                if ($dateChanged || $startChanged || $endChanged) {
                                    // Check if another slot exists with the new unique values (excluding current slot)
                                    if ($this->slotExists($doctorId, $normalizedDate, $start, $end, $slotId, $dayKeyLower)) {
                                        $dayName = ucfirst($dayKeyLower);
                                        $dateStr = $normalizedDate
                                            ? \Carbon\Carbon::parse($normalizedDate)->format('M d, Y')
                                            : 'recurring';

                                        $errors[] = "Duplicate slot: {$dayName} {$start}-{$end} on {$dateStr}";
                                        $totalSkipped++;
                                        $hasErrors = true;

                                        continue;
                                    }
                                }
                                // If unique fields haven't changed, just update without duplicate check

                                try {
                                    $existingSlot->update($availabilityData);
                                    $updatedSlots[] = $availabilityData;
                                    $totalUpdated++;
                                    // Mark as processed
                                    if ($slotId) {
                                        $processedSlotIds[$slotId] = true;
                                    }
                                } catch (UniqueConstraintViolationException $e) {
                                    // This should rarely happen, but handle it gracefully
                                    $dayName = ucfirst($dayKeyLower);
                                    $dateStr = $normalizedDate ? \Carbon\Carbon::parse($normalizedDate)->format('M d, Y') : 'recurring';
                                    $errors[] = "Cannot update slot: {$dayName} {$start}-{$end} on {$dateStr} - duplicate constraint violation";
                                    $totalSkipped++;
                                    $hasErrors = true;
                                }
                            } else {
                                // Slot ID exists but slot not found in DB - might have been deleted
                                // Treat as new slot
                                $slotId = null;
                            }
                        }

                        // Creating new slot (or slot with invalid ID)
                        if (! $slotId) {
                            // Check for duplicates - use normalized date
                            if ($this->slotExists($doctorId, $normalizedDate, $start, $end, null, $dayKeyLower)) {
                                $dayName = ucfirst($dayKeyLower);
                                $dateStr = $normalizedDate ? \Carbon\Carbon::parse($normalizedDate)->format('M d, Y') : 'recurring';
                                $errors[] = "Duplicate slot: {$dayName} {$start}-{$end} on {$dateStr}";
                                $totalSkipped++;
                                $hasErrors = true;

                                continue;
                            }

                            try {
                                $newSlot = DoctorAvailability::create($availabilityData);
                                $savedSlots[] = $availabilityData;
                                $totalSaved++;
                                // Mark as processed if it got an ID
                                if ($newSlot->id) {
                                    $processedSlotIds[$newSlot->id] = true;
                                }
                            } catch (UniqueConstraintViolationException $e) {
                                $dayName = ucfirst($dayKeyLower);
                                $dateStr = $normalizedDate ? \Carbon\Carbon::parse($normalizedDate)->format('M d, Y') : 'recurring';
                                $errors[] = "Duplicate slot: {$dayName} {$start}-{$end} on {$dateStr}";
                                $totalSkipped++;
                                $hasErrors = true;
                            }
                        }
                    }
                }

                // Handle Quick Add (temp_*) fields
                $tempDay = strtolower($data['temp_day'] ?? '');
                $tempStart = $this->normalizeTime($data['temp_start'] ?? null);
                $tempEnd = $this->normalizeTime($data['temp_end'] ?? null);

                if ($tempDay && $tempStart && $tempEnd) {
                    $isRec = (bool) ($data['temp_rec'] ?? false);
                    $tempDate = $isRec ? null : ($data['temp_date'] ?? null);

                    // Normalize date format
                    $normalizedTempDate = null;
                    if ($tempDate) {
                        try {
                            $normalizedTempDate = \Carbon\Carbon::parse($tempDate)->format('Y-m-d');
                        } catch (\Exception $e) {
                            $normalizedTempDate = null;
                        }
                    }

                    // Check for duplicates - use normalized date
                    $tempKey = ($normalizedTempDate ?? 'null') . '_' . $tempStart . '_' . $tempEnd;
                    if (isset($processedKeys[$tempKey])) {
                        return; // Already processed in the main loops
                    }

                    if ($this->slotExists($doctorId, $normalizedTempDate, $tempStart, $tempEnd, null, $tempDay)) {
                        $dayName = ucfirst($tempDay);
                        $dateStr = $normalizedTempDate ? \Carbon\Carbon::parse($normalizedTempDate)->format('M d, Y') : 'recurring';
                        $errors[] = "Duplicate quick-add slot: {$dayName} {$tempStart}-{$tempEnd} on {$dateStr}";
                        $totalSkipped++;
                        $hasErrors = true;
                    } else {
                        // Ensure time is in H:i:00 format
                        $tempStartFormatted = \Carbon\Carbon::parse($tempStart)->format('H:i:00');
                        $tempEndFormatted = \Carbon\Carbon::parse($tempEnd)->format('H:i:00');

                        // Only save required fields
                        $pendingData = [
                            'doctor_id' => $doctorId,
                            'day_of_week' => $tempDay,
                            'start_time' => $tempStartFormatted,
                            'end_time' => $tempEndFormatted,
                            'capacity' => (int) ($data['temp_cap'] ?? 1),
                            'consultation_type' => $data['temp_cons'] ?? 'in-person',
                            'opd_type' => $data['temp_opd'] ?? 'general',
                            'consultation_fee' => (float) ($data['temp_fee'] ?? 0),
                            'doctor_room' => $data['temp_room'] ?? null,
                            'is_recurring' => $isRec,
                            'is_available' => (bool) ($data['temp_active'] ?? true),
                        ];

                        if ($isRec) {

                            $pendingData['recurring_start_date'] = $this->calculateRecurringStartDate(
                                $tempDay,
                                $tempStart
                            );

                            $months = (int) ($data['temp_months'] ?? 3);

                            $startDate = \Carbon\Carbon::parse($pendingData['recurring_start_date']);

                            $pendingData['recurring_end_date'] = $startDate
                                ->copy()
                                ->addMonths($months)
                                ->format('Y-m-d');

                            $pendingData['recurring_months'] = $months;

                            $pendingData['date'] = null;
                        } else {
                            $pendingData['date'] = $normalizedTempDate;
                            $pendingData['recurring_start_date'] = null;
                            $pendingData['recurring_end_date'] = null;
                        }

                        try {
                            $selectedDay = strtolower($data['temp_day']);

                            $slotData = [
                                'start_time' => $tempStart,
                                'end_time' => $tempEnd,
                                'capacity' => (int) ($data['temp_cap'] ?? 1),
                                'consultation_type' => $data['temp_cons'] ?? 'in-person',
                                'opd_type' => $data['temp_opd'] ?? 'general',
                                'consultation_fee' => (float) ($data['temp_fee'] ?? 0),
                                'doctor_room' => $data['temp_room'] ?? null,
                                'is_recurring' => $isRec,
                                'is_available' => (bool) ($data['temp_active'] ?? true),
                            ];

                            if ($isRec) {
                                $slotData['recurring_start_date'] = $this->calculateRecurringStartDate(
                                    $selectedDay,
                                    $tempStart
                                );

                                $slotData['recurring_months'] = (int) ($data['temp_months'] ?? 3);
                                $slotData['date'] = null;
                            } else {
                                $slotData['date'] = $normalizedTempDate;
                            }

                            $existingSlots = $data["slots_{$selectedDay}"] ?? [];

                            $existingSlots[] = $slotData;

                            // 🔥 THIS IS THE KEY LINE
                            $data["slots_{$selectedDay}"] = $existingSlots;

                            $savedSlots[] = $pendingData;
                            $totalSaved++;
                        } catch (UniqueConstraintViolationException $e) {
                            $dayName = ucfirst($tempDay);
                            $dateStr = $normalizedTempDate ? \Carbon\Carbon::parse($normalizedTempDate)->format('M d, Y') : 'recurring';
                            $errors[] = "Duplicate quick-add slot: {$dayName} {$tempStart}-{$tempEnd} on {$dateStr}";
                            $totalSkipped++;
                            $hasErrors = true;
                        }
                    }
                }
            });

            // Show success/error notifications
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

                    // Show specific messages based on what was done
                    if ($isNewDoctor && $totalSaved > 0) {
                        // For new doctor creation
                        if ($totalSaved === 1) {
                            Notification::make()
                                ->title('Slot Added')
                                ->body('Added 1 new slot.')
                                ->success()
                                ->send();
                        } else {
                            Notification::make()
                                ->title('Slots Added')
                                ->body("Added {$totalSaved} new slots.")
                                ->success()
                                ->send();
                        }
                    } elseif ($totalSaved === 1 && $totalUpdated === 0) {
                        Notification::make()
                            ->title('Slot Created')
                            ->body('The availability slot has been created successfully.')
                            ->success()
                            ->send();
                    } elseif ($totalUpdated === 1 && $totalSaved === 0) {
                        Notification::make()
                            ->title('Update Successful')
                            ->body('Update data successfully.')
                            ->success()
                            ->send();
                    } elseif ($totalSaved > 0 && $totalUpdated > 0) {
                        $message = [];
                        if ($totalSaved > 0) {
                            $message[] = "{$totalSaved} slot(s) created";
                        }
                        if ($totalUpdated > 0) {
                            $message[] = "{$totalUpdated} slot(s) updated";
                        }
                        if ($totalSkipped > 0 && $hasErrors) {
                            $message[] = "{$totalSkipped} duplicate(s) skipped";
                        }

                        Notification::make()
                            ->title('Availability Saved')
                            ->body(implode(', ', $message) . '.')
                            ->success()
                            ->send();
                    } elseif ($totalSaved > 1) {
                        Notification::make()
                            ->title('Slots Created')
                            ->body("{$totalSaved} availability slots have been created successfully.")
                            ->success()
                            ->send();
                    } elseif ($totalUpdated > 1) {
                        Notification::make()
                            ->title('Update Successful')
                            ->body('Update data successfully.')
                            ->success()
                            ->send();
                    }
                } elseif ($hasErrors && $totalSkipped > 0 && $totalSaved === 0 && $totalUpdated === 0) {
                    Notification::make()
                        ->title('No Changes Saved')
                        ->body('All slots were duplicates. Please remove duplicate slots and try again.')
                        ->danger()
                        ->persistent()
                        ->send();
                }
            }
        } catch (\Throwable $e) {
            if ($notify) {
                Notification::make()
                    ->title('Error Saving Availability')
                    ->body($e->getMessage())
                    ->danger()
                    ->send();
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

    private function getValidationService(): \App\Services\DoctorAvailabilityValidationService
    {
        return app(\App\Services\DoctorAvailabilityValidationService::class);
    }

    private function normalizeTime($time): ?string
    {
        return $this->getValidationService()->normalizeTime($time);
    }

    private function slotExists(
        string $doctorId,
        ?string $date,
        string $startTime,
        string $endTime,
        ?string $excludeId = null,
        ?string $dayOfWeek = null
    ): bool {
        return $this->getValidationService()->slotExistsInDatabase(
            $doctorId,
            $date,
            $startTime,
            $endTime,
            $excludeId,
            $dayOfWeek
        );
    }

    private function calculateRecurringStartDate(string $day, $startTime): string
    {
        $now = \Carbon\Carbon::now();
        try {
            // Convert day name to Carbon day of week (0-6)
            $selectedDow = \Carbon\Carbon::parse($day)->dayOfWeek;
        } catch (\Exception $e) {
            // Fallback to today if day is invalid
            return $now->format('Y-m-d');
        }
        
        $todayDow = $now->dayOfWeek;

        $startTimeFormatted = $this->normalizeTime($startTime) ?: '00:00';
        $todayStartDateTime = \Carbon\Carbon::parse($now->format('Y-m-d') . ' ' . $startTimeFormatted);

        // If today is the selected day
        if ($todayDow === $selectedDow) {
            // If the time hasn't passed today, we can start today
            if ($now->lt($todayStartDateTime)) {
                return $now->format('Y-m-d');
            }
            // Otherwise, pick next week
            return $now->copy()->next($selectedDow)->format('Y-m-d');
        }

        // If it's a future day this week or next week
        return $now->copy()->next($selectedDow)->format('Y-m-d');
    }
}
