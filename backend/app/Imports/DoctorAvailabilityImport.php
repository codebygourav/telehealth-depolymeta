<?php

namespace App\Imports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Carbon\Carbon;

class DoctorAvailabilityImport implements ToCollection
{
    protected $doctor;
    protected $context;
    protected $results = [
        'totalSaved' => 0,
        'totalUpdated' => 0,
        'totalSkipped' => 0,
        'errors' => [],
        'hasErrors' => false,
    ];

    public function __construct($doctor, $context)
    {
        $this->doctor = $doctor;
        $this->context = $context;
    }

    public function getResults()
    {
        return $this->results;
    }

    public function collection(Collection $rows)
    {
        $data = [];

        foreach ($rows as $index => $row) {

            if ($index === 0) continue;

            $vService = app(\App\Services\DoctorAvailabilityValidationService::class);

            $isRecurring = in_array(strtolower(trim((string)($row[9] ?? ''))), ['true', '1', 'yes', 'y']);

            $startDate = $vService->normalizeDate($row[10] ?? null);
            $endDate = $vService->normalizeDate($row[11] ?? null);

            $months = null;
            if (isset($row[12])) {
                $value = trim((string) $row[12]);
                if ($value !== '' && is_numeric($value)) {
                    $months = (int) $value;
                }
            }

            $day = null;

            try {
                // Priority 1: From Recurring Start Date
                if ($isRecurring && $startDate) {
                    $day = strtolower(Carbon::parse($startDate)->format('l'));
                }

                // Priority 2: From Day Name Column (Index 13)
                if (!$day && !empty($row[13])) {
                    $d = strtolower(trim((string)$row[13]));
                    $days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
                    if (in_array($d, $days)) {
                        $day = $d;
                    }
                }

                // Priority 3: From Date/Day Column (Index 1)
                if (!$day && !empty($row[1])) {
                    $d = strtolower(trim((string)$row[1]));
                    $days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];

                    if (in_array($d, $days)) {
                        $day = $d;
                    } else {
                        $day = strtolower(Carbon::parse($row[1])->format('l'));
                    }
                }
            } catch (\Exception $e) {
                $this->results['errors'][] = "Row " . ($index + 1) . ": Invalid date/day format '{$row[1]}'";
                $this->results['hasErrors'] = true;
                continue;
            }

            if (!$day) {
                $this->results['errors'][] = "Row " . ($index + 1) . ": Could not resolve day (Provide Date/Day, Start Date, or Day Name)";
                $this->results['totalSkipped']++;
                $this->results['hasErrors'] = true;
                continue;
            }

            $startTime = $vService->normalizeTime($row[2] ?? null);
            $endTime = $vService->normalizeTime($row[3] ?? null);

            if (!$startTime || !$endTime) {
                $this->results['errors'][] = "Row " . ($index + 1) . ": Invalid time format";
                $this->results['totalSkipped']++;
                $this->results['hasErrors'] = true;
                continue;
            }

            $consultationType = $this->normalizeConsultationType($row[5] ?? null);
            $data["slots_{$day}"][] = [
                'date' => $isRecurring ? null : ($vService->normalizeDate($row[1] ?? null)),
                'start_time' => $startTime,
                'end_time' => $endTime,
                'capacity' => (int) ($row[4] ?? 1),
                'consultation_type' => $consultationType,
                'opd_type' => $this->normalizeOpdType($row[6] ?? null, $consultationType),
                'doctor_room' => $row[7] ?? null,
                'consultation_fee' => (float) ($row[8] ?? 0),
                'is_recurring' => $isRecurring,
                'recurring_start_date' => $startDate,
                'recurring_end_date' => $endDate,
                'recurring_months' => $isRecurring ? ($months ?? 3) : 0,
            ];
        }

        // 🔥 SAVE USING YOUR EXISTING LOGIC
        if (!empty($data)) {
            $this->results = $this->context->persistAvailabilitySlots($this->doctor, $data, false, false);
        }
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

        $normalizedValue = strtolower(trim((string) ($value ?? '')));
        if ($normalizedValue === '' || in_array($normalizedValue, ['n/a', 'na', '-', 'null'], true)) {
            return null;
        }

        $normalized = $normalizedValue;
        return in_array($normalized, ['general', 'private'], true) ? $normalized : 'general';
    }
}
