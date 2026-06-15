<?php

namespace App\Services;

use App\Models\DoctorAvailability;
use App\Models\DoctorAvailabilityOverride;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Filament\Notifications\Notification;
use App\Enums\{DayOfWeek};
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\UniqueConstraintViolationException;
use App\Http\Resources\WordPress\DoctorAvailabilityResource;

class DoctorAvailabilityService
{
    /**
     * Check if current user is super admin
     */
    public static function isSuperAdmin(): bool
    {
        $user = \Filament\Facades\Filament::auth()->user();
        return $user && ($user->hasRole('super_admin') || $user->hasRole('super-admin'));
    }

    /**
     * Get booked appointment dates for a given availability slot ID
     */
    public static function getBookedAppointmentDates(?string $slotId): array
    {
        if (!$slotId) {
            return [];
        }

        $slot = DoctorAvailability::find($slotId);
        if (!$slot) {
            return [];
        }

        return $slot->appointments()
            ->whereNotIn('status', [
                \App\Enums\AppointmentStatus::CANCELLED->value,
                \App\Enums\AppointmentStatus::FAILED->value,
            ])
            ->pluck('appointment_date')
            ->map(fn($date) => \Carbon\Carbon::parse($date)->format('d M Y'))
            ->unique()
            ->values()
            ->toArray();
    }

    /**
     * Expand recurring availabilities into discrete slots for a given window.
     */
    public function expandSlots(
        iterable $availabilities,
        Carbon $startDate,
        Carbon $endDate,
        bool $includePast = false,
        bool $skipBlocked = true,
    ): Collection {
        $allSlots = collect();
        $now = Carbon::now();

        foreach ($availabilities as $slot) {
            if ($this->isRecurringTemplate($slot)) {
                $overrides = $slot->relationLoaded('overrides')
                    ? $slot->overrides->keyBy(fn($override) => $override->override_date->format('Y-m-d'))
                    : DoctorAvailabilityOverride::query()
                    ->where('doctor_availability_id', $slot->id)
                    ->whereBetween('override_date', [$startDate->toDateString(), $endDate->toDateString()])
                    ->get()
                    ->keyBy(fn($override) => $override->override_date->format('Y-m-d'));

                $recurringStart = $slot->recurring_start_date
                    ? Carbon::parse($slot->recurring_start_date)->startOfDay()
                    : $startDate->copy();
                $recurringEnd = $slot->recurring_end_date
                    ? Carbon::parse($slot->recurring_end_date)->endOfDay()
                    : $endDate->copy();

                $rangeStart = $startDate->greaterThan($recurringStart) ? $startDate->copy() : $recurringStart;
                $rangeEnd = $recurringEnd->lessThan($endDate) ? $recurringEnd : $endDate;

                $dayNumbers = [
                    'sunday' => Carbon::SUNDAY,
                    'monday' => Carbon::MONDAY,
                    'tuesday' => Carbon::TUESDAY,
                    'wednesday' => Carbon::WEDNESDAY,
                    'thursday' => Carbon::THURSDAY,
                    'friday' => Carbon::FRIDAY,
                    'saturday' => Carbon::SATURDAY,
                ];
                $dow = $dayNumbers[$this->recurringDayOfWeek($slot, $recurringStart)] ?? $recurringStart->dayOfWeek;

                $current = $rangeStart->copy();

                // Move to the first occurrence of this day of week within our range
                if ($current->dayOfWeek !== $dow) {
                    $current->next($dow);
                }

                while ($current->lte($rangeEnd)) {
                    $dateStr = $current->toDateString();
                    $override = $overrides->get($dateStr);
                    $slotCopy = $this->applyEffectiveValuesForDate($slot, $dateStr, $override, $skipBlocked);

                    if ($slotCopy) {
                        $allSlots->push($slotCopy);
                    }

                    $current->addWeek();
                }
            } else {
                $dateObj = $slot->date ? Carbon::parse($slot->date) : null;
                if ($dateObj && $dateObj->betweenIncluded($startDate, $endDate)) {
                    if ($skipBlocked && $this->isDateBlocked($slot, $dateObj->toDateString())) {
                        continue;
                    }

                    $slot->date = $dateObj->toDateString();
                    $allSlots->push($slot);
                }
            }
        }

        return $allSlots->filter(function ($slot) use ($now, $includePast) {
            if ($includePast) {
                return true;
            }

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

    public function expandSlotsForApi(
        iterable $availabilities,
        ?Carbon $startDate = null,
        ?Carbon $endDate = null,
        bool $includePast = false,
        bool $skipBlocked = true,
    ): Collection {
        $startDate ??= Carbon::today();
        $endDate ??= $this->resolveApiEndDate($availabilities, $startDate);

        return $this->expandSlots($availabilities, $startDate, $endDate, $includePast, $skipBlocked);
    }

    /**
     * WordPress / public doctor profile: next N months, future slots only, includes blocked dates.
     */
    public function expandSlotsForWordPressApi(iterable $availabilities): Collection
    {
        $months = app(SlotBookingCutoffService::class)->wordpressAvailabilityMonths();
        $startDate = Carbon::today();
        $endDate = $startDate->copy()->addMonths($months)->endOfDay();

        return $this->expandSlots($availabilities, $startDate, $endDate, includePast: false, skipBlocked: false);
    }

    /**
     * @param  iterable<int, DoctorAvailability>  $availabilities
     */
    private function resolveApiEndDate(iterable $availabilities, Carbon $startDate): Carbon
    {
        $end = $startDate->copy()->addMonths(6);

        foreach ($availabilities as $slot) {
            if ($slot->recurring_end_date) {
                $recurringEnd = Carbon::parse($slot->recurring_end_date)->endOfDay();
                if ($recurringEnd->greaterThan($end)) {
                    $end = $recurringEnd;
                }
            }

            if ($slot->date) {
                $dateEnd = Carbon::parse($slot->date)->endOfDay();
                if ($dateEnd->greaterThan($end)) {
                    $end = $dateEnd;
                }
            }
        }

        return $end;
    }

    public function applyEffectiveValuesForDate(
        DoctorAvailability $availability,
        Carbon|string $date,
        ?DoctorAvailabilityOverride $override = null,
        bool $skipBlocked = true
    ): ?DoctorAvailability {
        $dateString = $date instanceof Carbon ? $date->toDateString() : Carbon::parse($date)->toDateString();
        $override ??= $this->overrideForDate($availability, $dateString);

        if ($skipBlocked && $this->isDateBlocked($availability, $dateString, $override)) {
            return null;
        }

        $slot = clone $availability;
        $slot->date = $dateString;
        $slot->effective_date = $dateString;
        $slot->override_id = $override?->id;
        $slot->override_status = $override?->status;
        $slot->is_recurring = $this->isRecurringTemplate($availability);
        $slot->source = $override ? 'override' : ($this->isRecurringTemplate($availability) ? 'recurring' : 'availability');

        if ($override) {
            $this->applyOverrideToExpandedSlot($slot, $override);
        }

        return $slot;
    }

    public function effectiveValuesForDate(DoctorAvailability $availability, Carbon|string $date): array
    {
        $dateString = $date instanceof Carbon ? $date->toDateString() : Carbon::parse($date)->toDateString();
        $override = $this->overrideForDate($availability, $dateString);

        return [
            'override' => $override,
            'status' => $override?->status ?? ($availability->is_available ? 'active' : 'blocked'),
            'start_time' => $override?->start_time ?? $availability->start_time,
            'end_time' => $override?->end_time ?? $availability->end_time,
            'capacity' => $override?->capacity ?? $availability->capacity ?? 1,
            'consultation_fee' => $override?->consultation_fee ?? $availability->consultation_fee ?? 0,
            'doctor_room' => $override?->doctor_room ?? $availability->doctor_room,
            'booking_cutoff_rules' => app(SlotBookingCutoffService::class)->resolveRulesForDate($availability, $override),
            'booking_cutoff_rules_source' => app(SlotBookingCutoffService::class)->rulesSourceForDate($availability, $override),
            'source' => $override ? 'override' : ($this->isRecurringTemplate($availability) ? 'recurring' : 'availability'),
        ];
    }

    public function overrideForDate(DoctorAvailability $availability, Carbon|string $date): ?DoctorAvailabilityOverride
    {
        if (! $this->isRecurringTemplate($availability) || ! $availability->exists || ! $availability->id) {
            return null;
        }

        $dateString = $date instanceof Carbon ? $date->toDateString() : Carbon::parse($date)->toDateString();

        if ($availability->relationLoaded('overrides')) {
            return $availability->overrides->first(
                fn(DoctorAvailabilityOverride $override) => $override->override_date->format('Y-m-d') === $dateString
            );
        }

        return $availability->overrides()->whereDate('override_date', $dateString)->first();
    }

    public function isDateBlocked(DoctorAvailability $availability, Carbon|string $date, ?DoctorAvailabilityOverride $override = null): bool
    {
        if (! $availability->is_available) {
            return true;
        }

        $dateString = $date instanceof Carbon ? $date->toDateString() : Carbon::parse($date)->toDateString();
        $override ??= $this->overrideForDate($availability, $dateString);

        if ($override && in_array($override->status, ['blocked', 'cancelled'], true)) {
            return true;
        }

        if (! $this->isRecurringTemplate($availability) || empty($availability->blocked_dates) || ! is_array($availability->blocked_dates)) {
            return false;
        }

        foreach ($availability->blocked_dates as $blockedDate) {
            if (is_array($blockedDate)) {
                $value = isset($blockedDate['date']) ? Carbon::parse($blockedDate['date'])->format('Y-m-d') : null;
                $blockedStart = isset($blockedDate['start_time']) ? Carbon::parse($blockedDate['start_time'])->format('H:i') : null;
                $blockedEnd = isset($blockedDate['end_time']) ? Carbon::parse($blockedDate['end_time'])->format('H:i') : null;
            } else {
                $value = Carbon::parse($blockedDate)->format('Y-m-d');
                $blockedStart = null;
                $blockedEnd = null;
            }

            if ($value === $dateString) {
                // If the blocked entry specifies a start_time and end_time, and the slot also specifies times,
                // check if the times match exactly to support slot-level blocking.
                if ($blockedStart && $blockedEnd && $availability->start_time && $availability->end_time) {
                    $slotStart = $availability->start_time instanceof Carbon ? $availability->start_time->format('H:i') : date('H:i', strtotime($availability->start_time));
                    $slotEnd = $availability->end_time instanceof Carbon ? $availability->end_time->format('H:i') : date('H:i', strtotime($availability->end_time));
                    if ($slotStart === $blockedStart && $slotEnd === $blockedEnd) {
                        return true;
                    }
                } else {
                    // Otherwise, the entire date is blocked for all slots.
                    return true;
                }
            }
        }

        return false;
    }

    public function isRecurringTemplate(DoctorAvailability $availability): bool
    {
        return (bool) $availability->is_recurring || (empty($availability->date) && filled($availability->day_of_week));
    }

    public function recurringDayOfWeek(DoctorAvailability $availability, ?Carbon $fallbackDate = null): string
    {
        if ($availability->day_of_week) {
            return strtolower($availability->day_of_week);
        }

        if ($availability->recurring_start_date) {
            return strtolower(Carbon::parse($availability->recurring_start_date)->format('l'));
        }

        return strtolower(($fallbackDate ?: Carbon::today())->format('l'));
    }

    private function applyOverrideToExpandedSlot(DoctorAvailability $slot, ?DoctorAvailabilityOverride $override): void
    {
        if (! $override) {
            $slot->override_id = null;
            $slot->override_status = null;
            $slot->source = 'recurring';

            return;
        }

        $slot->override_id = $override->id;
        $slot->override_status = $override->status;
        $slot->source = 'override';

        if ($override->start_time) {
            $slot->start_time = $override->start_time;
        }

        if ($override->end_time) {
            $slot->end_time = $override->end_time;
        }

        if ($override->capacity !== null) {
            $slot->capacity = $override->capacity;
        }

        if ($override->consultation_fee !== null) {
            $slot->consultation_fee = $override->consultation_fee;
        }

        if ($override->doctor_room !== null) {
            $slot->doctor_room = $override->doctor_room;
        }

        if ($override->booking_cutoff_rules !== null) {
            $slot->booking_cutoff_rules = $override->booking_cutoff_rules;
            $slot->booking_cutoff_rules_source = 'override';
        }
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

    /**
     * Group and format slots for the WordPress API.
     */
    public function formatSlotsForWordPressApi(Collection $slots): array
    {
        return $slots->groupBy('date')->map(function ($groupSlots, $date) {
            return [
                'date' => $date,
                'slots' => DoctorAvailabilityResource::collection($groupSlots),
            ];
        })->values()->all();
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

                        $normalizedRecurringStart = $isRecurring
                            ? $this->getValidationService()->normalizeDate($slot['recurring_start_date'] ?? null)
                            : null;
                        $normalizedRecurringEnd = $isRecurring
                            ? $this->getValidationService()->normalizeDate($slot['recurring_end_date'] ?? null)
                            : null;
                        $consultationType = $this->normalizeConsultationType($slot['consultation_type'] ?? 'in-person');

                        // Track this unique slot key to avoid duplicates within this same request.
                        $slotKey = implode('_', [
                            $dayKeyLower,
                            $normalizedDate ?? 'null',
                            $start,
                            $end,
                            $consultationType,
                            $normalizedRecurringStart ?? 'null',
                            $normalizedRecurringEnd ?? 'null',
                        ]);

                        if (isset($processedKeys[$slotKey])) {
                            $totalSkipped++;
                            continue;
                        }
                        $processedKeys[$slotKey] = true;

                        // Exact duplicates are expected during import reruns. Skip them quietly.
                        if (! $slotId && $this->slotExists($doctorId, $normalizedDate, $start, $end, $consultationType, null, $dayKeyLower, $normalizedRecurringStart, $normalizedRecurringEnd)) {
                            $totalSkipped++;
                            continue;
                        }

                        // Overlaps are real data conflicts and should still be reported.
                        $overlaps = $this->getValidationService()->slotOverlapsInDatabase(
                            $doctorId,
                            $normalizedDate,
                            $start,
                            $end,
                            $consultationType,
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

                        $normalizedOpdType = $this->normalizeOpdType($slot['opd_type'] ?? null, $consultationType);
                        $isChildOnly = $consultationType === 'in-person'
                            && (bool) ($slot['is_child_only'] ?? $slot['child'] ?? false);

                        // Normalize blocked_dates
                        $blockedDates = [];
                        if ($isRecurring && !empty($slot['blocked_dates']) && is_array($slot['blocked_dates'])) {
                            foreach ($slot['blocked_dates'] as $bd) {
                                if (is_array($bd) && !empty($bd['date'])) {
                                    $blockedDates[] = [
                                        'date' => \Carbon\Carbon::parse($bd['date'])->format('Y-m-d'),
                                        'start_time' => $bd['start_time'] ?? $startTimeFormatted,
                                        'end_time' => $bd['end_time'] ?? $endTimeFormatted,
                                    ];
                                } elseif (is_string($bd)) {
                                    $blockedDates[] = [
                                        'date' => \Carbon\Carbon::parse($bd)->format('Y-m-d'),
                                        'start_time' => $startTimeFormatted,
                                        'end_time' => $endTimeFormatted,
                                    ];
                                }
                            }
                        }
                        if (!empty($blockedDates)) {
                            $uniqueBlocked = [];
                            foreach ($blockedDates as $entry) {
                                $key = $entry['date'] . '_' . ($entry['start_time'] ?? '') . '_' . ($entry['end_time'] ?? '');
                                $uniqueBlocked[$key] = $entry;
                            }
                            $blockedDates = array_values($uniqueBlocked);
                        } else {
                            $blockedDates = null;
                        }

                        // Only save required fields for availability
                        $availabilityData = [
                            'doctor_id' => $doctorId,
                            'date' => $normalizedDate,
                            'day_of_week' => $dayKeyLower,
                            'start_time' => $startTimeFormatted,
                            'end_time' => $endTimeFormatted,
                            'capacity' => (int) ($slot['capacity'] ?? 1),
                            'consultation_type' => $consultationType,
                            'opd_type' => $normalizedOpdType,
                            'consultation_fee' => (float) ($slot['consultation_fee'] ?? 0),
                            'is_child_only' => $isChildOnly,
                            'doctor_room' => $slot['doctor_room'] ?? null,
                            'is_recurring' => $isRecurring,
                            'is_available' => (bool) ($slot['is_available'] ?? true),
                            'blocked_dates' => $blockedDates,
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
                            $availabilityData['recurring_months'] = 0;
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
                                $typeChanged = ($existingSlot->consultation_type ?? 'in-person') !== $consultationType;
                                $opdChanged = ($existingSlot->opd_type ?? null) !== $normalizedOpdType;
                                $childChanged = (bool) ($existingSlot->is_child_only ?? false) !== $isChildOnly;
                                $availableChanged = (bool) ($existingSlot->is_available ?? true) !== (bool) ($slot['is_available'] ?? true);
                                $recurringChanged = (bool) ($existingSlot->is_recurring ?? false) !== $isRecurring;

                                $existingRoom = empty(trim($existingSlot->doctor_room ?? '')) ? null : trim($existingSlot->doctor_room);
                                $newRoom = empty(trim($slot['doctor_room'] ?? '')) ? null : trim($slot['doctor_room']);
                                $roomChanged = $existingRoom !== $newRoom;

                                // Only update if something actually changed
                                $hasChanges = $dateChanged || $startChanged || $endChanged || $capacityChanged ||
                                    $feeChanged || $typeChanged || $opdChanged || $childChanged || $availableChanged || $recurringChanged || $roomChanged;

                                if (! $hasChanges) {
                                    // No changes, skip this slot
                                    if ($slotId) {
                                        $processedSlotIds[$slotId] = true;
                                    }

                                    continue;
                                }

                                if ($existingSlot->hasBookedAppointments()) {
                                    if (!self::isSuperAdmin()) {
                                        $errors[] = "You can't edit this slot for the doctor because it already has appointments.";
                                        $hasErrors = true;
                                        $totalSkipped++;

                                        if ($slotId) {
                                            $processedSlotIds[$slotId] = true;
                                        }

                                        continue;
                                    } else {
                                        $bookedDates = self::getBookedAppointmentDates($slotId);
                                        $errors[] = "⚠️ WARNING: Slot updated. It has booked appointments on: " . implode(', ', $bookedDates);
                                    }
                                }

                                // Only check for duplicates if the unique fields have changed
                                // This prevents "overlap with self" errors when updating non-time fields
                                if ($dateChanged || $startChanged || $endChanged) {
                                    // Check if another slot exists with the new unique values (excluding current slot)
                                    if ($this->slotExists($doctorId, $normalizedDate, $start, $end, $consultationType, $slotId, $dayKeyLower, $normalizedRecurringStart, $normalizedRecurringEnd)) {
                                        $totalSkipped++;
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
                            if ($this->slotExists($doctorId, $normalizedDate, $start, $end, $consultationType, null, $dayKeyLower, $normalizedRecurringStart, $normalizedRecurringEnd)) {
                                $totalSkipped++;
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
                                $totalSkipped++;
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
                    $tempConsultationType = $this->normalizeConsultationType($data['temp_cons'] ?? 'in-person');
                    $tempKey = ($normalizedTempDate ?? 'null') . '_' . $tempStart . '_' . $tempEnd . '_' . $tempConsultationType;
                    if (isset($processedKeys[$tempKey])) {
                        return; // Already processed in the main loops
                    }

                    if ($this->slotExists($doctorId, $normalizedTempDate, $tempStart, $tempEnd, $tempConsultationType, null, $tempDay)) {
                        $totalSkipped++;
                    } else {
                        // Ensure time is in H:i:00 format
                        $tempStartFormatted = \Carbon\Carbon::parse($tempStart)->format('H:i:00');
                        $tempEndFormatted = \Carbon\Carbon::parse($tempEnd)->format('H:i:00');

                        $tempOpdType = $this->normalizeOpdType($data['temp_opd'] ?? null, $tempConsultationType);
                        $tempIsChildOnly = $tempConsultationType === 'in-person'
                            && (bool) ($data['temp_is_child_only'] ?? $data['temp_child'] ?? false);

                        // Only save required fields
                        $pendingData = [
                            'doctor_id' => $doctorId,
                            'day_of_week' => $tempDay,
                            'start_time' => $tempStartFormatted,
                            'end_time' => $tempEndFormatted,
                            'capacity' => (int) ($data['temp_cap'] ?? 1),
                            'consultation_type' => $tempConsultationType,
                            'opd_type' => $tempOpdType,
                            'consultation_fee' => (float) ($data['temp_fee'] ?? 0),
                            'is_child_only' => $tempIsChildOnly,
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
                                'consultation_type' => $tempConsultationType,
                                'opd_type' => $tempOpdType,
                                'consultation_fee' => (float) ($data['temp_fee'] ?? 0),
                                'is_child_only' => $tempIsChildOnly,
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
                            $totalSkipped++;
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
                } elseif (! $hasErrors && $totalSkipped > 0 && $totalSaved === 0 && $totalUpdated === 0) {
                    Notification::make()
                        ->title('No New Slots Added')
                        ->body('All provided slots already existed and were skipped.')
                        ->warning()
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

    private function normalizeConsultationType($value): string
    {
        $normalized = strtolower(trim((string) ($value ?? 'in-person')));
        return $normalized === 'video' ? 'video' : 'in-person';
    }

    private function normalizeOpdType($value, string $consultationType): ?string
    {
        if ($consultationType === 'video') {
            return null;
        }

        $normalized = strtolower(trim((string) ($value ?? '')));
        if ($normalized === '' || in_array($normalized, ['n/a', 'na', '-', 'null'], true)) {
            return null;
        }

        return in_array($normalized, ['general', 'private'], true) ? $normalized : null;
    }

    private function slotExists(
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
        return $this->getValidationService()->slotExistsInDatabase(
            $doctorId,
            $date,
            $startTime,
            $endTime,
            $consultationType,
            $excludeId,
            $dayOfWeek,
            $recurringStartDate,
            $recurringEndDate
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
