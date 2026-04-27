<?php

namespace App\Imports;

use App\Models\Doctor;
use App\Models\User;
use App\Services\DoctorAvailabilityService;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Carbon\Carbon;

class BulkDoctorAvailabilityImport implements ToCollection
{
    protected $fixedDoctorId;
    protected $service;
    protected $results = [
        'totalSaved' => 0,
        'totalUpdated' => 0,
        'totalSkipped' => 0,
        'errors' => [],
        'hasErrors' => false,
    ];

    public function __construct($fixedDoctorId = null)
    {
        $this->fixedDoctorId = $fixedDoctorId;
        $this->service = app(DoctorAvailabilityService::class);
    }

    public function collection(Collection $rows)
    {
        // Skip header
        if ($rows->count() <= 1) return;
        
        $header = $rows->first();
        $dataRows = $rows->slice(1);

        // Group rows by doctor
        $groupedByDoctor = [];

        foreach ($dataRows as $index => $row) {
            // Trim all strings in the row
            $row = array_map(fn($v) => is_string($v) ? trim($v) : $v, $row->toArray());

            $email = $row[0] ?? '';
            // Skip empty rows or placeholder rows silently
            if (empty($email) || strtolower($email) === 'n/a' || strtolower($email) === 'doctor email') {
                continue;
            }

            $doctor = $this->resolveDoctor($row);
            if (!$doctor) {
                $this->results['errors'][] = "Email: " . $email . " - Row " . ($index + 2) . ": Doctor record not found";
                $this->results['totalSkipped']++;
                $this->results['hasErrors'] = true;
                continue;
            }

            $day = $this->resolveDay($row);
            if (!$day) {
                $this->results['errors'][] = "Email: " . $email . " - Row " . ($index + 2) . ": Could not resolve day (missing Date/Day/Recurring Start Date)";
                $this->results['totalSkipped']++;
                $this->results['hasErrors'] = true;
                continue;
            }

            $slotData = $this->formatSlotData($row);
            if (!$slotData) {
                $this->results['errors'][] = "Email: " . $email . " - Row " . ($index + 2) . ": Invalid time or capacity";
                $this->results['totalSkipped']++;
                $this->results['hasErrors'] = true;
                continue;
            }

            $groupedByDoctor[$doctor->id]["slots_{$day}"][] = $slotData;
        }

        // Persist for each doctor
        foreach ($groupedByDoctor as $doctorId => $data) {
            $doctor = Doctor::find($doctorId);
            if ($doctor) {
                // Call service to persist
                $res = $this->service->persistAvailabilitySlots($doctor, $data, false, false);
                
                $this->results['totalSaved'] += $res['totalSaved'];
                $this->results['totalUpdated'] += $res['totalUpdated'];
                $this->results['totalSkipped'] += $res['totalSkipped'];
                
                $doctorEmail = $doctor->user->email ?? 'Unknown Doctor';
                if (!empty($res['errors'])) {
                    foreach (array_unique($res['errors']) as $error) {
                        $msg = $error;
                        // Simplify long SQL errors
                        if (str_contains($error, 'SQLSTATE')) {
                            $msg = "Database warning/error (likely invalid data format for some columns)";
                        }
                        $this->results['errors'][] = "Email: {$doctorEmail} - " . $msg;
                    }
                }

                if ($res['hasErrors']) {
                    $this->results['hasErrors'] = true;
                }
            }
        }
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
        $email = $row[0] ?? null;
        if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return null;
        }

        $user = User::where('email', $email)->first();
        return $user ? $user->doctor : null;
    }

    protected function resolveDay($row)
    {
        $vService = app(\App\Services\DoctorAvailabilityValidationService::class);
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
        $vService = app(\App\Services\DoctorAvailabilityValidationService::class);
        
        $startTime = $vService->normalizeTime($row[2] ?? null);
        $endTime = $vService->normalizeTime($row[3] ?? null);

        if (!$startTime || !$endTime) {
            return null;
        }

        $isRecurring = in_array(strtolower(trim((string)($row[9] ?? ''))), ['true', '1', 'yes', 'y']);
        $consType = $this->clean($row[5]) ?? 'in-person';

        return [
            'date' => $isRecurring ? null : ($vService->normalizeDate($row[1] ?? null)),
            'start_time' => $startTime,
            'end_time' => $endTime,
            'capacity' => (int) ($this->clean($row[4]) ?? 1),
            'consultation_type' => $consType,
            'opd_type' => $this->clean($row[6]) ?? ($consType === 'video' ? null : 'general'),
            'doctor_room' => $this->clean($row[7]),
            'consultation_fee' => (float) ($this->clean($row[8]) ?? 0),
            'is_recurring' => $isRecurring,
            'recurring_start_date' => $vService->normalizeDate($row[10] ?? null),
            'recurring_end_date' => $vService->normalizeDate($row[11] ?? null),
            'recurring_months' => $isRecurring ? (int) ($this->clean($row[12]) ?? 3) : null,
        ];
    }
}
