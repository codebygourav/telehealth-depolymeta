<?php

namespace App\Imports;

use App\Models\Doctor;
use App\Models\DoctorAvailability;
use App\Models\User;
use App\Services\DoctorAvailabilityValidationService;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\ToCollection;

class BulkDoctorAvailabilityImport implements ToCollection
{
    protected $fixedDoctorId;
    protected $validationService;
    protected $results = [
        'totalSaved' => 0,
        'totalUpdated' => 0,
        'totalSkipped' => 0,
        'errors' => [],
        'hasErrors' => false,
    ];
    protected array $processedKeys = [];
    protected array $skipReasonCounts = [];

    public function __construct($fixedDoctorId = null)
    {
        $this->fixedDoctorId = $fixedDoctorId;
        $this->validationService = app(DoctorAvailabilityValidationService::class);
    }

    public function collection(Collection $rows)
    {
        // Skip header
        if ($rows->count() <= 1) return;

        $dataRows = $rows->slice(1)->values();
        Log::info('BulkDoctorAvailabilityImport started', [
            'fixed_doctor_id' => $this->fixedDoctorId,
            'row_count_including_header' => $rows->count(),
            'data_row_count' => $dataRows->count(),
        ]);

        foreach ($dataRows as $index => $row) {
            try {
                $rowData = $row instanceof Collection ? $row->toArray() : (array) $row;
                // Trim all strings in the row
                $rowData = array_map(fn($v) => is_string($v) ? trim($v) : $v, $rowData);

                $email = $this->normalizeEmail($rowData[0] ?? '');
                // Skip empty rows or placeholder rows silently
                if ($email === '' || strtolower($email) === 'n/a' || strtolower($email) === 'doctor email') {
                    continue;
                }

                $doctor = $this->resolveDoctor($rowData);
                if (!$doctor) {
                    $this->markSkipped("Email: " . $email . " - Row " . ($index + 2) . ": Doctor record not found", 'doctor_not_found');
                    continue;
                }

                $day = $this->resolveDay($rowData);
                if (!$day) {
                    $this->markSkipped("Email: " . $email . " - Row " . ($index + 2) . ": Could not resolve day (missing Date/Day/Recurring Start Date)", 'invalid_day');
                    continue;
                }

                $slotData = $this->formatSlotData($rowData);
                if (!$slotData) {
                    $this->markSkipped("Email: " . $email . " - Row " . ($index + 2) . ": Invalid time or capacity", 'invalid_slot_data');
                    continue;
                }

                $this->importRow($doctor, $day, $slotData, $index + 2, $email);
            } catch (\Throwable $e) {
                $safeEmail = is_array($rowData ?? null) ? $this->normalizeEmail($rowData[0] ?? '') : '';
                $safeEmail = $safeEmail !== '' ? $safeEmail : 'unknown';
                $this->markSkipped(
                    "Email: {$safeEmail} - Row " . ($index + 2) . ": Row processing failed - {$e->getMessage()}",
                    'row_exception'
                );
                continue;
            }
        }

        Log::info('BulkDoctorAvailabilityImport finished', [
            'fixed_doctor_id' => $this->fixedDoctorId,
            'total_saved' => $this->results['totalSaved'],
            'total_updated' => $this->results['totalUpdated'],
            'total_skipped' => $this->results['totalSkipped'],
            'has_errors' => $this->results['hasErrors'],
            'skip_reason_counts' => $this->skipReasonCounts,
            'sample_errors' => array_slice($this->results['errors'], 0, 25),
        ]);
    }

    protected function clean($v)
    {
        $v = is_string($v) ? trim($v) : $v;
        if (empty($v) || in_array(strtolower((string)$v), ['n/a', 'na', '-', 'null'])) {
            return null;
        }
        return (string)$v;
    }

    public function getResults()
    {
        return $this->results;
    }

    protected function resolveDoctor($row)
    {
        if ($this->fixedDoctorId) {
            return Doctor::find($this->fixedDoctorId);
        }

        // Look for Doctor Email in columns (assuming Column A is Doctor Email if not fixed)
        $email = $this->normalizeEmail($row[0] ?? null);
        if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return null;
        }

        $user = User::whereRaw('LOWER(email) = ?', [strtolower($email)])->first();
        return $user ? $user->doctor : null;
    }

    protected function resolveDay($row)
    {
        $vService = $this->validationService;
        $isRecurring = in_array(strtolower(trim((string)($row[9] ?? ''))), ['true', '1', 'yes', 'y']);
        $startDate = $vService->normalizeDate($row[10] ?? null);

        $day = null;

        // Priority 1: From Recurring Start Date
        if ($isRecurring && $startDate) {
            try {
                $day = strtolower(Carbon::parse($startDate)->format('l'));
            } catch (\Exception $e) {}
        }

        // Priority 2: From Day Name Column (Index 13) - NEW
        if (!$day && !empty($row[13])) {
            $d = strtolower(trim((string)$row[13]));
            $days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
            if (in_array($d, $days)) {
                $day = $d;
            }
        }

        // Priority 3: From Date/Day Column (Index 1)
        if (!$day && !empty($row[1])) {
            try {
                $d = strtolower(trim((string)$row[1]));
                $days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
                if (in_array($d, $days)) {
                    $day = $d;
                } else {
                    $day = strtolower(Carbon::parse($row[1])->format('l'));
                }
            } catch (\Exception $e) {}
        }

        return $day;
    }

    protected function formatSlotData($row)
    {
        $vService = $this->validationService;

        $startTime = $vService->normalizeTime($row[2] ?? null);
        $endTime = $vService->normalizeTime($row[3] ?? null);
        $isRecurring = in_array(strtolower(trim((string)($row[9] ?? ''))), ['true', '1', 'yes', 'y']);
        $normalizedDate = $isRecurring ? null : $vService->normalizeDate($row[1] ?? null);

        if (!$startTime || !$endTime) {
            return null;
        }

        // For one-time slots, a concrete date is mandatory.
        if (!$isRecurring && !$normalizedDate) {
            return null;
        }

        $consType = $this->normalizeConsultationType($row[5] ?? null);

        return [
            'date' => $normalizedDate,
            'start_time' => $startTime,
            'end_time' => $endTime,
            'capacity' => (int) ($this->clean($row[4]) ?? 1),
            'consultation_type' => $consType,
            'opd_type' => $this->normalizeOpdType($row[6] ?? null, $consType),
            'doctor_room' => $this->clean($row[7]),
            'consultation_fee' => (float) ($this->clean($row[8]) ?? 0),
            'is_recurring' => $isRecurring,
            'recurring_start_date' => $vService->normalizeDate($row[10] ?? null),
            'recurring_end_date' => $vService->normalizeDate($row[11] ?? null),
            'recurring_months' => $isRecurring ? (int) ($this->clean($row[12]) ?? 3) : 0,
        ];
    }

    protected function importRow(Doctor $doctor, string $day, array $slotData, int $rowNumber, string $email): void
    {
        $start = $this->validationService->normalizeTime($slotData['start_time'] ?? null);
        $end = $this->validationService->normalizeTime($slotData['end_time'] ?? null);

        if (! $start || ! $end) {
            $this->markSkipped("Email: {$email} - Row {$rowNumber}: Invalid time format", 'invalid_time');
            return;
        }

        $isRecurring = (bool) ($slotData['is_recurring'] ?? false);
        $normalizedDate = $isRecurring ? null : $this->validationService->normalizeDate($slotData['date'] ?? null);
        $normalizedRecurringStart = $isRecurring ? $this->validationService->normalizeDate($slotData['recurring_start_date'] ?? null) : null;
        $normalizedRecurringEnd = $isRecurring ? $this->validationService->normalizeDate($slotData['recurring_end_date'] ?? null) : null;
        $normalizedDay = strtolower(trim($day));

        // Prevent duplicate rows in the same import file from being inserted.
        $importKey = implode('|', [
            $doctor->id,
            $isRecurring ? 'recurring' : 'one-time',
            $normalizedDate ?? 'null',
            $normalizedDay,
            $start,
            $end,
            $normalizedRecurringStart ?? 'null',
            $normalizedRecurringEnd ?? 'null',
        ]);
        if (isset($this->processedKeys[$importKey])) {
            $this->results['totalSkipped']++;
            $this->incrementSkipReason('duplicate_in_file');
            return;
        }
        $this->processedKeys[$importKey] = true;

        if ($this->validationService->slotExistsInDatabase(
            $doctor->id,
            $normalizedDate,
            $start,
            $end,
            $slotData['consultation_type'] ?? 'in-person',
            null,
            $normalizedDay,
            $normalizedRecurringStart,
            $normalizedRecurringEnd
        )) {
            $this->results['totalSkipped']++;
            $this->incrementSkipReason('already_exists');
            return;
        }

        $overlaps = $this->validationService->slotOverlapsInDatabase(
            $doctor->id,
            $normalizedDate,
            $start,
            $end,
            $slotData['consultation_type'] ?? 'in-person',
            null,
            $normalizedDay,
            $normalizedRecurringStart,
            $normalizedRecurringEnd
        );

        if (! empty($overlaps)) {
            $overlap = $overlaps[0];
            $this->markSkipped(
                "Email: {$email} - Row {$rowNumber}: Time overlap with existing {$overlap['start_time']}-{$overlap['end_time']}",
                'time_overlap'
            );
            return;
        }

        $consultationType = $this->normalizeConsultationType($slotData['consultation_type'] ?? null);
        $availabilityData = [
            'doctor_id' => $doctor->id,
            'day_of_week' => $normalizedDay,
            'start_time' => Carbon::parse($start)->format('H:i:00'),
            'end_time' => Carbon::parse($end)->format('H:i:00'),
            'capacity' => max(1, (int) ($slotData['capacity'] ?? 1)),
            'consultation_type' => $consultationType,
            'opd_type' => $this->normalizeOpdType($slotData['opd_type'] ?? null, $consultationType),
            'consultation_fee' => (float) ($slotData['consultation_fee'] ?? 0),
            'doctor_room' => $slotData['doctor_room'] ?? null,
            'is_recurring' => $isRecurring,
            'is_available' => true,
            'date' => $normalizedDate,
            'recurring_start_date' => $normalizedRecurringStart,
            'recurring_end_date' => $normalizedRecurringEnd,
            'recurring_months' => $isRecurring ? max(1, (int) ($slotData['recurring_months'] ?? 3)) : 0,
        ];

        try {
            DoctorAvailability::create($availabilityData);
            $this->results['totalSaved']++;
        } catch (UniqueConstraintViolationException $e) {
            $this->results['totalSkipped']++;
            $this->incrementSkipReason('duplicate_constraint');
        } catch (ValidationException $e) {
            $messages = collect($e->errors())->flatten()->implode(' ');
            $this->markSkipped("Email: {$email} - Row {$rowNumber}: {$messages}", 'validation_exception');
        } catch (\Throwable $e) {
            $this->markSkipped("Email: {$email} - Row {$rowNumber}: {$e->getMessage()}", 'unexpected_exception');
        }
    }

    protected function normalizeEmail($email): string
    {
        $email = trim((string) $email, " \t\n\r\0\x0B\"'");
        return strtolower($email);
    }

    protected function markSkipped(string $message, string $reason): void
    {
        $this->results['errors'][] = $message;
        $this->results['totalSkipped']++;
        $this->results['hasErrors'] = true;
        $this->incrementSkipReason($reason);
        Log::warning('BulkDoctorAvailabilityImport row skipped', [
            'reason' => $reason,
            'message' => $message,
        ]);
    }

    protected function incrementSkipReason(string $reason): void
    {
        if (! isset($this->skipReasonCounts[$reason])) {
            $this->skipReasonCounts[$reason] = 0;
        }
        $this->skipReasonCounts[$reason]++;
    }

    protected function normalizeConsultationType($value): string
    {
        $normalized = strtolower((string) ($this->clean($value) ?? 'in-person'));
        return $normalized === 'video' ? 'video' : 'in-person';
    }

    protected function normalizeOpdType($value, string $consultationType): ?string
    {
        if ($consultationType === 'video') {
            return null;
        }

        $cleaned = $this->clean($value);
        if ($cleaned === null) {
            return null;
        }

        $normalized = strtolower((string) $cleaned);
        return in_array($normalized, ['general', 'private'], true) ? $normalized : 'general';
    }
}
