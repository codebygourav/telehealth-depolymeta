<?php

namespace App\Http\Resources\WordPress;

use App\Services\DoctorAvailabilityService;
use App\Services\SettingService;
use App\Services\SlotBookingCutoffService;
use App\Services\SlotCapacityService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DoctorAvailabilityResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $now = Carbon::now();
        $availabilityService = app(DoctorAvailabilityService::class);
        $cutoffService = app(SlotBookingCutoffService::class);

        $dateValue = $this->date
            ? (is_string($this->date) ? $this->date : $this->date->toDateString())
            : null;

        $slotStartTime = $this->resolveSlotStartTime($dateValue);

        $isAdminBlocked = $dateValue
            ? $availabilityService->isDateBlocked($this->resource, $dateValue)
            : ! $this->is_available;

        $cutoffRules = $cutoffService->rulesForAvailability($this->resource);
        $cutoffSource = $this->booking_cutoff_rules_source
            ?? ($this->override_id ? 'override' : (is_array($this->booking_cutoff_rules) && $this->booking_cutoff_rules !== [] ? 'availability' : 'app_default'));

        $blockedBefore = $cutoffService->configuredBeforeForApi($cutoffRules, $slotStartTime, $now);

        $isCutoffBlocked = $slotStartTime
            ? $cutoffService->isWithinAnyCutoff($slotStartTime, $cutoffRules, $now)
            : false;

        $isBlocked = $isAdminBlocked || $isCutoffBlocked;

        $capacitySummary = $dateValue
            ? app(SlotCapacityService::class)->summary(
                doctorId: $this->doctor->id,
                date: $dateValue,
                startTime: $this->start_time,
                capacity: (int) ($this->capacity ?? 1),
                availabilityId: $this->id,
                consultationType: $this->consultation_type,
            )
            : [
                'booked_count' => 0,
                'available_slots' => (int) ($this->capacity ?? 1),
                'is_full' => false,
            ];

        $dayName = $this->resolveDayName($dateValue);

        $bookable = $slotStartTime
            && $slotStartTime->greaterThan($now)
            && ! $isBlocked
            && ! $capacitySummary['is_full'];

        return [
            'id' => $this->id,
            'doctor_id' => $this->doctor->id,
            'date' => $dateValue,
            'day_of_week' => $dayName,
            'booking_start_time' => $this->start_time ? Carbon::parse($this->start_time)->format('H:i:s') : null,
            'start_time' => $this->formatTimeAmPmLocal($this->start_time),
            'end_time' => $this->formatTimeAmPmLocal($this->end_time),
            'consultation_type' => $this->consultation_type,
            'consultation_type_label' => $this->consultation_type === 'video'
                ? 'Video'
                : 'Clinic Visit',
            'capacity' => $this->capacity,
            'booked_count' => $capacitySummary['booked_count'],
            'available' => $bookable,
            'is_blocked' => $isBlocked,
            'booking_cutoff_rules_source' => $cutoffSource,
            'blocked_before' => $blockedBefore,
            ...($this->consultation_type === 'in-person' && $this->opd_type ? ['opd_type' => $this->opd_type] : []),
            'is_child_only' => $this->is_child_only,
            'child_age' => $this->is_child_only ? SettingService::getChildAgeLimit() : null,
            'consultation_fee' => isset($this->consultation_fee) ? (int) round($this->consultation_fee) : null,
            'doctor_room' => $this->doctor_room,
            'available_slots' => $capacitySummary['available_slots'],
            'is_full' => $capacitySummary['is_full'],
        ];
    }

    private function resolveSlotStartTime(?string $dateValue): ?Carbon
    {
        if (! $dateValue || ! $this->start_time) {
            return null;
        }

        $timeString = $this->start_time instanceof Carbon
            ? $this->start_time->format('H:i:s')
            : Carbon::parse($this->start_time)->format('H:i:s');

        return Carbon::parse($dateValue . ' ' . $timeString);
    }

    private function resolveDayName(?string $dateValue): ?string
    {
        if ($dateValue) {
            return strtolower(Carbon::parse($dateValue)->format('l'));
        }

        if ($this->day_of_week) {
            return strtolower($this->day_of_week);
        }

        if ($this->recurring_start_date) {
            return strtolower(Carbon::parse($this->recurring_start_date)->format('l'));
        }

        return null;
    }

    private function formatTimeAmPmLocal($time)
    {
        if (! $time) {
            return null;
        }

        try {
            return Carbon::parse($time)->format('g:i A');
        } catch (\Exception $e) {
            return $time;
        }
    }
}