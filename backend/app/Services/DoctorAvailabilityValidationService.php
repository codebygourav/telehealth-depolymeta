<?php

namespace App\Services;

use App\Models\DoctorAvailability;
use Carbon\Carbon;

class DoctorAvailabilityValidationService
{
    
    private function normalizeModelDateValue($value): ?string
    {
        if ($value instanceof Carbon) {
            return $value->format('Y-m-d');
        }

        return $this->normalizeDate($value);
    }

    private function normalizeConsultationType(?string $consultationType): string
    {
        return strtolower((string) $consultationType) === 'video' ? 'video' : 'in-person';
    }

    private function isNonRecurringSlotInFutureWindow(?string $date, string $endTime): bool
    {
        $normalizedDate = $this->normalizeDate($date);
        $normalizedEnd = $this->normalizeTime($endTime);

        if (!$normalizedDate || !$normalizedEnd) {
            return true;
        }

        try {
            return Carbon::parse("{$normalizedDate} {$normalizedEnd}")->isFuture();
        } catch (\Exception $e) {
            return true;
        }
    }

    private function hasRecurringWindowPassed(?string $recurringEndDate): bool
    {
        $normalizedRecurringEnd = $this->normalizeDate($recurringEndDate);
        if (!$normalizedRecurringEnd) {
            return false;
        }

        try {
            return Carbon::parse($normalizedRecurringEnd)->endOfDay()->isPast();
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * ----------------------------------------------------------------------
     * GLOBAL AVAILABILITY RULES (Telehealth)
     * ----------------------------------------------------------------------
     * - A doctor cannot be available in two places at the same time.
     * - For a given doctor and (date OR recurring pattern):
     *   - time ranges must be valid (start < end, non-empty)
     *   - time ranges must not overlap
     *   - exact duplicate slots are not allowed
     *
     * This service is the single source of truth for all logical checks.
     * UI layers (forms, slide-overs, quick add) should call into these
     * helpers instead of re-implementing validation logic.
     */

    public function normalizeTime($time): ?string
    {
        if ($time instanceof \DateTimeInterface) {
            return $time->format('H:i');
        }

        if (is_array($time)) {
            return (isset($time['hour']) && isset($time['minute']))
                ? sprintf('%02d:%02d', $time['hour'], $time['minute']) : null;
        }

        if (is_numeric($time)) {
            try {
                return \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($time)->format('H:i');
            } catch (\Exception $e) {
                // Fallback or ignore
            }
        }

        if (is_string($time)) {
            $time = trim($time);
            if (empty($time)) return null;

            try {
                return Carbon::parse($time)->format('H:i');
            } catch (\Exception $e) {
                return null;
            }
        }
        return null;
    }

    public function normalizeDate($date): ?string
    {
        if (empty($date)) {
            return null;
        }

        if (is_numeric($date)) {
            try {
                return \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($date)->format('Y-m-d');
            } catch (\Exception $e) {
                // Fallback
            }
        }

        try {
            return Carbon::parse($date)->format('Y-m-d');
        } catch (\Exception $e) {
            return null;
        }
    }

    public function validateTimeRange(string $startTime, string $endTime): array
    {
        $errors = [];

        $start = $this->normalizeTime($startTime);
        $end = $this->normalizeTime($endTime);

        if (!$start || !$end) {
            $errors[] = 'Both start time and end time are required for availability.';
            return $errors;
        }

        $startCarbon = Carbon::createFromFormat('H:i', $start);
        $endCarbon = Carbon::createFromFormat('H:i', $end);

        if ($startCarbon->gte($endCarbon)) {
            $errors[] = 'Start time must be earlier than end time. A consultation cannot end before (or at) its start time.';
        }

        return $errors;
    }


    public function timeRangesOverlap(
        string $start1,
        string $end1,
        string $start2,
        string $end2,
        ?string $consultationType1 = null,
        ?string $consultationType2 = null
    ): bool {
        // If consultation types are both set and different, treat as non-overlapping
        if (
            $consultationType1 !== null &&
            $consultationType2 !== null &&
            $consultationType1 !== $consultationType2
        ) {
            return false;
        }

        $start1Norm = $this->normalizeTime($start1);
        $end1Norm = $this->normalizeTime($end1);
        $start2Norm = $this->normalizeTime($start2);
        $end2Norm = $this->normalizeTime($end2);

        if (!$start1Norm || !$end1Norm || !$start2Norm || !$end2Norm) {
            return false;
        }

        $start1Carbon = Carbon::createFromFormat('H:i', $start1Norm);
        $end1Carbon = Carbon::createFromFormat('H:i', $end1Norm);
        $start2Carbon = Carbon::createFromFormat('H:i', $start2Norm);
        $end2Carbon = Carbon::createFromFormat('H:i', $end2Norm);

        return $start1Carbon->lt($end2Carbon) && $start2Carbon->lt($end1Carbon);
    }

    /**
     * Returns true if there is an exact slot (with same doctor, date, start_time, end_time)
     */
    public function slotExistsInDatabase(
        string $doctorId,
        ?string $date,
        string $startTime,
        string $endTime,
        ?string $consultationType = null,
        ?string $excludeId = null,
        ?string $dayOfWeek = null,
        ?string $recurringStartDate = null,
        ?string $recurringEndDate = null
    ): bool {
        $normalizedDate = $this->normalizeDate($date);
        $normalizedStart = $this->normalizeTime($startTime);
        $normalizedEnd = $this->normalizeTime($endTime);
        $normalizedRecurringStart = $this->normalizeDate($recurringStartDate);
        $normalizedRecurringEnd = $this->normalizeDate($recurringEndDate);

        if (!$normalizedStart || !$normalizedEnd) {
            return false;
        }

        // For recurring slots, if dayOfWeek is not provided, try to get it from context/record
        // However, in slotExistsInDatabase we usually have it passed or it's on the record.
        // If we are checking "exists", we need to know WHICH day were are talking about.

        $isSqlite = \Illuminate\Support\Facades\DB::connection()->getDriverName() === 'sqlite';
        $timeFormatRawStart = $isSqlite 
            ? "strftime('%H:%M', start_time) = ?" 
            : "TIME_FORMAT(start_time, '%H:%i') = ?";
        $timeFormatRawEnd = $isSqlite 
            ? "strftime('%H:%M', end_time) = ?" 
            : "TIME_FORMAT(end_time, '%H:%i') = ?";

        $query = DoctorAvailability::where('doctor_id', $doctorId)
            ->where(function ($q) use ($normalizedStart, $timeFormatRawStart) {
                $q->where('start_time', $normalizedStart)
                  ->orWhereRaw($timeFormatRawStart, [$normalizedStart]);
            })
            ->where(function ($q) use ($normalizedEnd, $timeFormatRawEnd) {
                $q->where('end_time', $normalizedEnd)
                  ->orWhereRaw($timeFormatRawEnd, [$normalizedEnd]);
            });

        if ($normalizedDate) {
            $query->where('date', $normalizedDate);
        } else {
            $query->whereNull('date');
            if ($dayOfWeek) {
                $query->where('day_of_week', strtolower($dayOfWeek));
            }
            if ($normalizedRecurringStart) {
                $query->where('recurring_start_date', $normalizedRecurringStart);
            }
            if ($normalizedRecurringEnd) {
                $query->where('recurring_end_date', $normalizedRecurringEnd);
            }
        }

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        if ($consultationType !== null) {
            $query->where('consultation_type', $this->normalizeConsultationType($consultationType));
        }

        // Exclude soft-deleted records and ignore expired windows
        return $query->get()->contains(function ($slot) {
            if (!$slot->is_recurring) {
                return $this->isNonRecurringSlotInFutureWindow(
                    $slot->date?->format('Y-m-d') ?? $slot->date,
                    $slot->end_time
                );
            }

            return !$this->hasRecurringWindowPassed(
                $slot->recurring_end_date?->format('Y-m-d') ?? $slot->recurring_end_date
            );
        });
    }

    /**
     * Check if a slot overlaps with existing slots in the database
     */
    public function slotOverlapsInDatabase(
        string $doctorId,
        ?string $date,
        string $startTime,
        string $endTime,
        ?string $consultationType = null,
        ?string $excludeId = null,
        ?string $dayOfWeek = null,
        ?string $recurringStartDate = null,
        ?string $recurringEndDate = null
    ): array {
        $overlaps = [];
        $normalizedDate = $this->normalizeDate($date);
        $normalizedStart = $this->normalizeTime($startTime);
        $normalizedEnd = $this->normalizeTime($endTime);
        $normalizedConsultationType = $this->normalizeConsultationType($consultationType);
        $normalizedRecurringStart = $this->normalizeDate($recurringStartDate);
        $normalizedRecurringEnd = $this->normalizeDate($recurringEndDate);

        if (!$normalizedStart || !$normalizedEnd) {
            return $overlaps;
        }

        // Get all existing slots for this doctor (check by day_of_week for recurring, by date for non-recurring)
        $query = DoctorAvailability::where('doctor_id', $doctorId);

        if ($normalizedDate) {
            // For specific dates, check that exact date
            $query->where('date', $normalizedDate);
        } else {
            // For recurring slots (date is null), we must match the day.
            $query->whereNull('date');
            if ($dayOfWeek) {
                $targetDay = strtolower($dayOfWeek);
                $query->where('day_of_week', $targetDay);
            }
        }

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        // Explicitly filter out soft-deleted slots by checking 'deleted_at' is null
        $query->whereNull('deleted_at');

        $existingSlots = $query->get();

        foreach ($existingSlots as $slot) {
            if (!$slot->is_recurring && !$this->isNonRecurringSlotInFutureWindow(
                $this->normalizeModelDateValue($slot->date),
                $slot->end_time
            )) {
                continue;
            }

            if ($slot->is_recurring && $this->hasRecurringWindowPassed(
                $this->normalizeModelDateValue($slot->recurring_end_date)
            )) {
                continue;
            }

            if (! $normalizedDate && ! $this->recurringRangesOverlap(
                $normalizedRecurringStart,
                $normalizedRecurringEnd,
                $this->normalizeModelDateValue($slot->recurring_start_date),
                $this->normalizeModelDateValue($slot->recurring_end_date)
            )) {
                continue;
            }

            $existingStart = $this->normalizeTime($slot->start_time);
            $existingEnd = $this->normalizeTime($slot->end_time);
            $existingConsultationType = $this->normalizeConsultationType($slot->consultation_type ?? null);

            // Allow identical time windows across different consultation modes.
            if (
                $existingConsultationType !== $normalizedConsultationType &&
                $existingStart === $normalizedStart &&
                $existingEnd === $normalizedEnd
            ) {
                continue;
            }

            // Allow "overlap" only if consultation types are the same; allow identical time for different types (e.g., in-person and video)
            if (
                $existingStart && $existingEnd &&
                $this->timeRangesOverlap(
                    $normalizedStart,
                    $normalizedEnd,
                    $existingStart,
                    $existingEnd,
                    $normalizedConsultationType,
                    $existingConsultationType
                )
            ) {
                $overlaps[] = [
                    'id' => $slot->id,
                    'start_time' => $existingStart,
                    'end_time' => $existingEnd,
                    'date' => $slot->date,
                ];
            }

        }

        return $overlaps;
    }

    private function recurringRangesOverlap(
        ?string $startA,
        ?string $endA,
        ?string $startB,
        ?string $endB
    ): bool {
        $normalizedStartA = $this->normalizeDate($startA) ?? '0001-01-01';
        $normalizedEndA = $this->normalizeDate($endA) ?? '9999-12-31';
        $normalizedStartB = $this->normalizeDate($startB) ?? '0001-01-01';
        $normalizedEndB = $this->normalizeDate($endB) ?? '9999-12-31';

        return $normalizedStartA <= $normalizedEndB && $normalizedStartB <= $normalizedEndA;
    }

    /**
     * Validate a single slot against database and form data
     * Returns array of error messages (empty if valid)
     */
    public function validateSlot(
        ?string $doctorId,
        ?string $date,
        string $startTime,
        string $endTime,
        ?string $consultationType = 'in-person',
        bool $isRecurring = false,
        ?string $slotId = null,
        array $existingFormSlots = [],
        ?string $dayOfWeek = null,
        ?string $recurringEndDate = null
    ): array {
        $errors = [];

        // 1. Validate time range (start < end)
        $timeErrors = $this->validateTimeRange($startTime, $endTime);
        if (!empty($timeErrors)) {
            $errors = array_merge($errors, $timeErrors);
            return $errors; // Don't continue if time is invalid
        }

        $normalizedDate = $isRecurring ? null : $this->normalizeDate($date);
        $normalizedStart = $this->normalizeTime($startTime);
        $normalizedEnd = $this->normalizeTime($endTime);
        $normalizedConsultationType = $this->normalizeConsultationType($consultationType);

        if (!$normalizedStart || !$normalizedEnd) {
            $errors[] = 'Both start time and end time are required for availability.';
            return $errors;
        }

        // Past one-time slots should not block scheduling.
        if (!$isRecurring && !$this->isNonRecurringSlotInFutureWindow($normalizedDate, $normalizedEnd)) {
            return $errors;
        }

        // Recurring slots whose recurrence window already ended should not block scheduling.
        if ($isRecurring && $this->hasRecurringWindowPassed($recurringEndDate)) {
            return $errors;
        }

        // 2. Check for exact duplicates in form data
        foreach ($existingFormSlots as $existingSlot) {
            if (!is_array($existingSlot)) {
                continue;
            }

            // Skip if it's the same slot being edited
            if ($slotId && isset($existingSlot['id']) && $existingSlot['id'] === $slotId) {
                continue;
            }

            $existingStart = $this->normalizeTime($existingSlot['start_time'] ?? null);
            $existingEnd = $this->normalizeTime($existingSlot['end_time'] ?? null);
            $existingIsRecurring = (bool)($existingSlot['is_recurring'] ?? false);
            $existingDate = $existingIsRecurring ? null : $this->normalizeDate($existingSlot['date'] ?? null);
            $existingRecurringEndDate = $existingSlot['recurring_end_date'] ?? null;
            $existingConsultationType = $this->normalizeConsultationType($existingSlot['consultation_type'] ?? null);

            if (!$existingStart || !$existingEnd) {
                continue;
            }

            if (!$existingIsRecurring && !$this->isNonRecurringSlotInFutureWindow($existingDate, $existingEnd)) {
                continue;
            }

            if ($existingIsRecurring && $this->hasRecurringWindowPassed($existingRecurringEndDate)) {
                continue;
            }

            $currentDay = strtolower($dayOfWeek ?? '');

            $existingDay = strtolower($existingSlot['day_of_week'] ?? '');

            if (
                $normalizedStart === $existingStart &&
                $normalizedEnd === $existingEnd &&
                $normalizedConsultationType === $existingConsultationType &&
                $isRecurring === $existingIsRecurring &&
                (
                    // Non-recurring → compare exact date
                    (!$isRecurring && $normalizedDate === $existingDate) ||

                    // Recurring → compare day of week
                    ($isRecurring && $currentDay === $existingDay)
                )
            ) {
                $dateStr = $isRecurring
                    ? ucfirst($currentDay)
                    : ($normalizedDate ? Carbon::parse($normalizedDate)->format('M d, Y') : 'unknown date');

                $errors[] = "This availability already exists for {$dateStr}: {$normalizedStart}–{$normalizedEnd}.";
                return $errors;
            }

            // Check for time overlap (only if same date or both recurring)
            $currentDay = strtolower($dayOfWeek ?? '');
            $existingDay = strtolower($existingSlot['day_of_week'] ?? '');

            if (
                ($normalizedDate === $existingDate && !$isRecurring) ||
                ($isRecurring && $existingIsRecurring && $currentDay === $existingDay)
            ) {
                if (
                    $normalizedConsultationType !== $existingConsultationType &&
                    $normalizedStart === $existingStart &&
                    $normalizedEnd === $existingEnd
                ) {
                    continue;
                }

                if ($this->timeRangesOverlap($normalizedStart, $normalizedEnd, $existingStart, $existingEnd, $normalizedConsultationType, $existingConsultationType)) {
                    $dateStr = $normalizedDate
                        ? Carbon::parse($normalizedDate)->format('M d, Y')
                        : 'this recurring pattern';
                    $errors[] = "This time overlaps with an existing availability slot ({$existingStart}–{$existingEnd} on {$dateStr}). A doctor cannot be booked for two consultations at the same time.";
                    return $errors;
                }
            }
        }

        // 3. Check against database (if doctor exists)
        if ($doctorId) {
            // Check for exact duplicate
            if ($this->slotExistsInDatabase($doctorId, $normalizedDate, $normalizedStart, $normalizedEnd, $normalizedConsultationType, $slotId, $dayOfWeek)) {
                $dateStr = $normalizedDate
                    ? Carbon::parse($normalizedDate)->format('M d, Y')
                    : 'this recurring pattern';
                $modeLabel = $normalizedConsultationType === 'video' ? 'Video' : 'In-Person';
                $errors[] = "A {$modeLabel} slot already exists from {$normalizedStart} to {$normalizedEnd} on {$dateStr}. Use a different time or change the consultation type.";
                return $errors;
            }

            // Check for overlaps
            $overlaps = $this->slotOverlapsInDatabase($doctorId, $normalizedDate, $normalizedStart, $normalizedEnd, $normalizedConsultationType, $slotId, $dayOfWeek);
            if (!empty($overlaps)) {
                $overlap = $overlaps[0];
                $dateStr = $normalizedDate
                    ? Carbon::parse($normalizedDate)->format('M d, Y')
                    : 'this recurring pattern';
                $errors[] = "This time overlaps with another availability ({$overlap['start_time']}–{$overlap['end_time']} on {$dateStr}). A doctor cannot be available in two places at the same time.";
                return $errors;
            }
        }

        return $errors;
    }

    /**
     * Validate all slots in form data
     * Returns array of error messages grouped by slot
     */
    public function validateAllSlots(?string $doctorId, array $formData): array
    {
        $allErrors = [];
        $allFormSlots = [];

        // First, collect ALL slots from form data with their day information
        $dayLabels = \App\Enums\DayOfWeek::labels();
        foreach (array_keys($dayLabels) as $day) {
            $dayKeyLower = strtolower($day);
            $slots = $formData["slots_{$day}"] ?? $formData["slots_{$dayKeyLower}"] ?? [];

            if (!is_array($slots)) {
                continue;
            }

            foreach ($slots as $slotIndex => $slot) {
                if (!is_array($slot)) {
                    continue;
                }

                $startTime = $slot['start_time'] ?? null;
                $endTime = $slot['end_time'] ?? null;

                if (!$startTime || !$endTime) {
                    continue;
                }

                // Store slot with day information
                $allFormSlots[] = [
                    'slot' => $slot,
                    'day' => $dayKeyLower,
                    'dayName' => ucfirst($dayKeyLower),
                ];
            }
        }

        // Now validate each slot against all other slots
        foreach ($allFormSlots as $currentIndex => $currentSlotData) {
            $slot = $currentSlotData['slot'];
            $dayKeyLower = $currentSlotData['day'];
            $dayName = $currentSlotData['dayName'];

            $startTime = $slot['start_time'] ?? null;
            $endTime = $slot['end_time'] ?? null;
            $date = $slot['date'] ?? null;
            $isRecurring = (bool)($slot['is_recurring'] ?? false);
            $slotId = $slot['id'] ?? null;

            if (!$startTime || !$endTime) {
                continue;
            }

            // Build list of OTHER slots (excluding current slot) for comparison
            $otherSlots = [];
            foreach ($allFormSlots as $otherIndex => $otherSlotData) {
                if ($otherIndex === $currentIndex) {
                    continue; // Skip current slot
                }
                $otherSlot = $otherSlotData['slot'];
                // Also exclude if it's the same slot being edited (by ID)
                if ($slotId && isset($otherSlot['id']) && $otherSlot['id'] === $slotId) {
                    continue;
                }
                $otherSlots[] = $otherSlot;
            }

            // Validate this slot
            $errors = $this->validateSlot(
                $doctorId,
                $date,
                $startTime,
                $endTime,
                $slot['consultation_type'] ?? 'in-person',
                $isRecurring,
                $slotId,
                $otherSlots,
                $dayKeyLower
            );

            if (!empty($errors)) {
                $allErrors[] = "[{$dayName}] " . implode(' ', $errors);
            }
        }

        // Check quick-add fields
        $tempDay = strtolower($formData['temp_day'] ?? '');
        $tempStart = $formData['temp_start'] ?? null;
        $tempEnd = $formData['temp_end'] ?? null;
        $tempDate = $formData['temp_date'] ?? null;
        $tempRec = (bool)($formData['temp_rec'] ?? false);

        if ($tempDay && $tempStart && $tempEnd) {
            // Convert all form slots to simple array for comparison
            $allSlotsForComparison = array_map(fn($data) => $data['slot'], $allFormSlots);

            $errors = $this->validateSlot(
                $doctorId,
                $tempDate,
                $tempStart,
                $tempEnd,
                $formData['temp_cons'] ?? 'in-person',
                $tempRec,
                null,
                $allSlotsForComparison,
                $tempDay
            );

            if (!empty($errors)) {
                $dayName = ucfirst($tempDay);
                $allErrors[] = "[Quick Add - {$dayName}] " . implode(' ', $errors);
            }
        }

        return $allErrors;
    }
}