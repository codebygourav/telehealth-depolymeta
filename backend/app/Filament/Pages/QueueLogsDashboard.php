<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use App\Models\Doctor;
use App\Models\AppointmentQueueLog;
use App\Traits\HasCustomSidebar;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Livewire\WithPagination;

class QueueLogsDashboard extends Page
{
    use HasCustomSidebar;
    use WithPagination;

    protected string $view = 'filament.pages.queue-logs-dashboard';
    protected static ?string $title = 'Queue Logs & Audit';
    protected static ?string $slug = 'queue-logs';

    // State properties
    public ?string $logDoctorId = null;
    public string $logTab = 'timeline';
    public ?string $logFromDate = null;
    public ?string $logToDate = null;
    public string $doctorSearchQuery = '';
    public string $logTypeFilter = 'all';
    public ?int $selectedSlotIndex = null;

    public static function getSidebarOptions(): array
    {
        return [
            'label' => 'Queue Logs & Audit',
            'icon'  => 'heroicon-o-document-text',
            'sort'  => 16,
            'group' => 'Appointments & Finance',
            'visible' => true,
        ];
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canViewAny();
    }

    public static function canViewAny(): bool
    {
        $user = Auth::user();
        return $user && (
            $user->hasRole('super_admin') ||
            $user->hasRole('doctor_manager') ||
            $user->hasRole('doctor') ||
            $user->hasRole('receptionist') ||
            $user->can('appointments.view') ||
            $user->can('appointments.view_any')
        );
    }

    public function mount(): void
    {
        $this->logFromDate = now()->toDateString();
        $this->logToDate = now()->toDateString();
        $this->logTab = 'timeline';

        if (Auth::user()?->hasRole('doctor')) {
            $doc = Doctor::where('user_id', Auth::id())->first();
            if ($doc) {
                $this->logDoctorId = $doc->id;
            }
        }
    }

    public function updatedLogDoctorId()
    {
        $this->resetPage();
        $this->selectedSlotIndex = null;
    }

    public function updatedLogFromDate()
    {
        $this->resetPage();
        $this->selectedSlotIndex = null;
    }

    public function updatedLogToDate()
    {
        $this->resetPage();
        $this->selectedSlotIndex = null;
    }

    public function updatedDoctorSearchQuery()
    {
        $this->resetPage();
    }

    public function updatedLogTypeFilter()
    {
        $this->resetPage();
    }

    public function selectDoctor(?string $doctorId): void
    {
        $this->logDoctorId = $doctorId;
        $this->selectedSlotIndex = null;
        $this->resetPage();
    }

    public function selectTab(string $tab): void
    {
        $this->logTab = $tab;
        $this->resetPage();
    }

    public function applyFilters(): void
    {
        $this->resetPage();
    }

    // Get list of doctors with stats/initials for Sidebar
    public function getDoctors(): array
    {
        $query = Doctor::active();

        if (!empty($this->doctorSearchQuery)) {
            $query->where(function ($q) {
                $q->where('first_name', 'like', '%' . $this->doctorSearchQuery . '%')
                  ->orWhere('last_name', 'like', '%' . $this->doctorSearchQuery . '%');
            });
        }

        // Filter based on logged-in doctor
        if (Auth::user()?->hasRole('doctor')) {
            $query->where('user_id', Auth::id());
        }

        $doctors = $query->with('departments')->get();
        $result = [];

        foreach ($doctors as $doctor) {
            $initials = strtoupper(substr($doctor->first_name ?? 'D', 0, 1) . substr($doctor->last_name ?? '', 0, 1));
            $dept = $doctor->departments->first()?->name ?? 'General Practice';
            
            // Count logs today
            $logCount = \App\Models\AppointmentQueueLog::where('doctor_id', $doctor->id)
                ->whereDate('created_at', Carbon::today())
                ->count();

            $result[] = [
                'id' => $doctor->id,
                'name' => "Dr. {$doctor->first_name} {$doctor->last_name}",
                'initials' => $initials,
                'department' => $dept,
                'log_count_today' => $logCount,
                'status' => $this->getDoctorLiveStatus($doctor->id),
            ];
        }

        return $result;
    }

    protected function getQueueLogsQuery(): Builder
    {
        $query = AppointmentQueueLog::query()
            ->with(['appointment.patient', 'creator', 'doctor']);

        if ($this->logDoctorId) {
            $query->where('doctor_id', $this->logDoctorId);
        }

        $appTimezone = config('app.timezone') ?: 'UTC';
        $from = $this->logFromDate ? Carbon::parse($this->logFromDate, $appTimezone)->startOfDay() : Carbon::today($appTimezone)->startOfDay();
        $to = $this->logToDate ? Carbon::parse($this->logToDate, $appTimezone)->endOfDay() : Carbon::today($appTimezone)->endOfDay();

        // If single day and slot is selected, restrict time bounds to slot bounds
        if ($this->logFromDate === $this->logToDate && !is_null($this->selectedSlotIndex) && $this->logDoctorId) {
            $stats = $this->getTimingStatsForDate($this->logDoctorId, $this->logFromDate);
            if (isset($stats['slots'][$this->selectedSlotIndex])) {
                $slot = $stats['slots'][$this->selectedSlotIndex];
                $from = $slot['start'];
                
                // Find next slot start to bound window
                $nextSlotStart = isset($stats['slots'][$this->selectedSlotIndex + 1]) 
                    ? $stats['slots'][$this->selectedSlotIndex + 1]['start'] 
                    : $slot['end']->copy()->endOfDay();
                
                $to = $slot['end']->copy()->addHours(2);
                if ($to->gt($nextSlotStart)) {
                    $to = $nextSlotStart;
                }
            }
        }

        $query->whereBetween('created_at', [$from, $to]);

        $actions = $this->getActionsForLogType($this->logTypeFilter);
        if ($actions) {
            $query->whereIn('action', $actions);
        }

        return $query;
    }

    // Get queue action logs
    public function getQueueLogs()
    {
        return $this->getQueueLogsQuery()
            ->orderBy('created_at', 'desc')
            ->paginate(20);
    }

    // Helper to format date & time according to d/m/Y and H:i
    public function formatDateTime(?Carbon $dateTime, bool $showTime = true): string
    {
        if (!$dateTime) {
            return '—';
        }
        if ($showTime) {
            return $dateTime->format('d/m/Y H:i');
        }
        return $dateTime->format('d/m/Y');
    }

    // Helper to format duration like 4h 25m or 35m
    public function formatDurationMinutes(int $seconds): string
    {
        $totalMinutes = round($seconds / 60);
        $hours = floor($totalMinutes / 60);
        $minutes = $totalMinutes % 60;
        
        $parts = [];
        if ($hours > 0) {
            $parts[] = "{$hours}h";
        }
        if ($minutes > 0 || empty($parts)) {
            $parts[] = "{$minutes}m";
        }
        
        return implode(' ', $parts);
    }

    // Get shift timing & active calculations for a doctor on a specific date
    public function getTimingStatsForDate(string $doctorId, string $dateStr): array
    {
        $date = Carbon::parse($dateStr);
        $appTimezone = config('app.timezone') ?: 'UTC';
        
        // 0. Handle future dates
        if ($date->isAfter(Carbon::today())) {
            return [
                'date' => $date->toDateString(),
                'is_future' => true,
                'slots' => [],
                'overall' => [
                    'check_in' => null,
                    'last_app_end' => null,
                    'first_consult_start' => null,
                    'total_break_seconds' => 0,
                    'active_seconds' => 0,
                    'extra_seconds' => 0,
                    'breaks' => [],
                ],
            ];
        }

        // 1. Get Scheduled Shifts (availabilities)
        $availabilities = \App\Models\DoctorAvailability::where('doctor_id', $doctorId)
            ->where(function ($query) use ($date) {
                $query->whereDate('date', $date)
                    ->orWhere(function ($q) use ($date) {
                        $q->where('is_recurring', true)
                            ->where('day_of_week', strtolower($date->format('l')))
                            ->whereNull('deleted_at');
                    });
            })
            ->orderBy('start_time', 'asc')
            ->get();

        $slots = [];
        $shiftIntervals = [];
        if ($availabilities->isNotEmpty()) {
            foreach ($availabilities as $avail) {
                $startStr = $avail->start_time instanceof Carbon 
                    ? $avail->start_time->format('H:i:s') 
                    : Carbon::parse($avail->start_time)->format('H:i:s');
                $endStr = $avail->end_time instanceof Carbon 
                    ? $avail->end_time->format('H:i:s') 
                    : Carbon::parse($avail->end_time)->format('H:i:s');

                $sTime = Carbon::parse($date->toDateString() . ' ' . $startStr, $appTimezone);
                $eTime = Carbon::parse($date->toDateString() . ' ' . $endStr, $appTimezone);

                $slots[] = ['start' => $sTime, 'end' => $eTime];
                $shiftIntervals[] = $sTime->format('H:i') . ' - ' . $eTime->format('H:i');
            }
        } else {
            // Fallback to first/last appointments
            $firstApp = \App\Models\Appointment::where('doctor_id', $doctorId)
                ->whereDate('appointment_date', $date)
                ->orderBy('appointment_time', 'asc')
                ->first();

            $lastApp = \App\Models\Appointment::where('doctor_id', $doctorId)
                ->whereDate('appointment_date', $date)
                ->orderBy('appointment_time', 'desc')
                ->first();

            if ($firstApp) {
                $sTime = Carbon::parse($date->toDateString() . ' ' . $firstApp->appointment_time, $appTimezone);
                $eTime = Carbon::parse($date->toDateString() . ' ' . ($lastApp->appointment_end_time ?? $lastApp->appointment_time), $appTimezone);
                $slots[] = ['start' => $sTime, 'end' => $eTime];
                $shiftIntervals[] = $sTime->format('H:i') . ' - ' . $eTime->format('H:i');
            }
        }

        // Fetch all logs for the day using timezone-aware bounds
        $startOfDayLocal = $date->copy()->startOfDay();
        $endOfDayLocal = $date->copy()->endOfDay();
        $dayLogs = \App\Models\AppointmentQueueLog::where('doctor_id', $doctorId)
            ->whereBetween('created_at', [$startOfDayLocal, $endOfDayLocal])
            ->orderBy('created_at', 'asc')
            ->get();

        $slotsBreakdown = [];
        $overallActiveSeconds = 0;
        $overallTotalBreakSeconds = 0;
        $overallExtraSeconds = 0;
        $overallCheckIn = null;
        $overallLastAppEnd = null;
        $overallFirstConsult = null;

        // Calculate stats for each individual slot
        foreach ($slots as $idx => $slot) {
            $sStart = $slot['start'];
            $sEnd = $slot['end'];

            // Define search windows for slot-specific logs
            $prevSlotEnd = $idx > 0 ? $slots[$idx - 1]['end'] : $sStart->copy()->startOfDay();
            $nextSlotStart = $idx < count($slots) - 1 ? $slots[$idx + 1]['start'] : $sEnd->copy()->endOfDay();

            // Check-in search window: from end of previous slot (or start of day) to slot end
            $checkInWindowStart = $sStart->copy()->subHour();
            if ($checkInWindowStart->lt($prevSlotEnd)) {
                $checkInWindowStart = $prevSlotEnd;
            }

            // Find check-in inside the window
            $slotCheckInLog = $dayLogs->filter(function ($log) use ($checkInWindowStart, $sEnd) {
                return $log->action === 'check_in' && $log->created_at->between($checkInWindowStart, $sEnd);
            })->first();

            $slotCheckIn = $slotCheckInLog ? $slotCheckInLog->created_at->copy()->setTimezone($appTimezone) : null;

            // Fallback: If no check-in is found, check if there is an active session from an earlier check-in
            if (!$slotCheckIn) {
                $earlierCheckIn = $dayLogs->filter(function ($log) use ($sStart) {
                    return $log->action === 'check_in' && $log->created_at->lt($sStart);
                })->last();

                if ($earlierCheckIn) {
                    $hasCheckOut = $dayLogs->filter(function ($log) use ($earlierCheckIn, $sStart) {
                        return $log->action === 'check_out' && $log->created_at->between($earlierCheckIn->created_at, $sStart);
                    })->isNotEmpty();

                    if (!$hasCheckOut) {
                        $slotCheckIn = $earlierCheckIn->created_at->copy()->setTimezone($appTimezone);
                    }
                }
            }

            // Calculate check-in delay
            $checkInDiffMin = 0;
            if ($slotCheckIn) {
                $checkInDiffMin = round(($slotCheckIn->timestamp - $sStart->timestamp) / 60);
                if (!$overallCheckIn || $slotCheckIn->lt($overallCheckIn)) {
                    $overallCheckIn = $slotCheckIn;
                }
            }

            // First consult start
            $slotFirstStartLog = $dayLogs->filter(function ($log) use ($sStart, $sEnd) {
                return $log->action === 'start' && $log->created_at->between($sStart->copy()->subMinutes(30), $sEnd);
            })->first();

            $slotFirstConsult = $slotFirstStartLog ? $slotFirstStartLog->created_at->copy()->setTimezone($appTimezone) : null;
            if ($slotFirstConsult && (!$overallFirstConsult || $slotFirstConsult->lt($overallFirstConsult))) {
                $overallFirstConsult = $slotFirstConsult;
            }

            // Checkout / Last End search window: from slot start to next slot start (or end of day)
            $checkoutWindowEnd = $sEnd->copy()->addHours(2);
            if ($checkoutWindowEnd->gt($nextSlotStart)) {
                $checkoutWindowEnd = $nextSlotStart;
            }

            $slotLastEndLog = $dayLogs->filter(function ($log) use ($sStart, $checkoutWindowEnd) {
                return in_array($log->action, ['complete', 'skip', 'not_complete']) 
                    && $log->created_at->between($sStart, $checkoutWindowEnd);
            })->last();

            $slotCheckOutLog = $dayLogs->filter(function ($log) use ($sStart, $checkoutWindowEnd) {
                return $log->action === 'check_out' && $log->created_at->between($sStart, $checkoutWindowEnd);
            })->last();

            $slotLastEnd = null;
            if ($slotLastEndLog && $slotCheckOutLog) {
                $slotLastEnd = $slotLastEndLog->created_at->gt($slotCheckOutLog->created_at)
                    ? $slotLastEndLog->created_at->copy()->setTimezone($appTimezone)
                    : $slotCheckOutLog->created_at->copy()->setTimezone($appTimezone);
            } elseif ($slotLastEndLog) {
                $slotLastEnd = $slotLastEndLog->created_at->copy()->setTimezone($appTimezone);
            } elseif ($slotCheckOutLog) {
                $slotLastEnd = $slotCheckOutLog->created_at->copy()->setTimezone($appTimezone);
            }

            if ($slotLastEnd && (!$overallLastAppEnd || $slotLastEnd->gt($overallLastAppEnd))) {
                $overallLastAppEnd = $slotLastEnd;
            }

            // Checkout difference (relative to slot end)
            $checkoutDiffMin = 0;
            if ($slotLastEnd) {
                $checkoutDiffMin = round(($slotLastEnd->timestamp - $sEnd->timestamp) / 60);
            }

            // Breaks taken inside this slot
            $slotBreakLogs = $dayLogs->filter(function ($log) use ($sStart, $checkoutWindowEnd) {
                return in_array($log->action, ['break_start', 'break_end']) 
                    && $log->created_at->between($sStart, $checkoutWindowEnd);
            });

            $slotBreaks = [];
            $slotTotalBreakSec = 0;
            $currBreakStart = null;

            foreach ($slotBreakLogs as $blog) {
                $time = $blog->created_at->copy()->setTimezone($appTimezone);
                if ($blog->action === 'break_start') {
                    $currBreakStart = $time;
                } elseif ($blog->action === 'break_end' && $currBreakStart) {
                    $dur = $time->timestamp - $currBreakStart->timestamp;
                    if ($dur < 0) $dur = 0;
                    $slotTotalBreakSec += $dur;
                    $slotBreaks[] = [
                        'start' => $currBreakStart,
                        'end' => $time,
                        'duration' => $dur
                    ];
                    $currBreakStart = null;
                }
            }

            // Handle ongoing break today
            if ($currBreakStart && $date->isToday()) {
                $timeNow = now()->setTimezone($appTimezone);
                $dur = $timeNow->timestamp - $currBreakStart->timestamp;
                if ($dur < 0) $dur = 0;
                $slotTotalBreakSec += $dur;
                $slotBreaks[] = [
                    'start' => $currBreakStart,
                    'end' => null,
                    'duration' => $dur
                ];
            }

            $overallTotalBreakSeconds += $slotTotalBreakSec;

            // Active seconds in slot
            $slotActiveSec = 0;
            if ($slotCheckIn && $slotLastEnd) {
                $totalPeriod = $slotLastEnd->timestamp - $slotCheckIn->timestamp;
                $slotActiveSec = $totalPeriod - $slotTotalBreakSec;
                if ($slotActiveSec < 0) $slotActiveSec = 0;
            }
            $overallActiveSeconds += $slotActiveSec;

            // Extra seconds in slot
            $slotExtraSec = 0;
            if ($slotLastEnd && $slotLastEnd->timestamp > $sEnd->timestamp) {
                $slotExtraSec = $slotLastEnd->timestamp - $sEnd->timestamp;
            }
            $overallExtraSeconds += $slotExtraSec;

            $slotsBreakdown[] = [
                'label' => $sStart->format('H:i') . ' - ' . $sEnd->format('H:i'),
                'start' => $sStart,
                'end' => $sEnd,
                'check_in' => $slotCheckIn,
                'first_consult_start' => $slotFirstConsult,
                'last_app_end' => $slotLastEnd,
                'breaks' => $slotBreaks,
                'total_break_seconds' => $slotTotalBreakSec,
                'active_seconds' => $slotActiveSec,
                'extra_seconds' => $slotExtraSec,
                'check_in_diff_minutes' => $checkInDiffMin,
                'checkout_diff_minutes' => $checkoutDiffMin,
            ];
        }

        // Cumulative overall day break calculation (all breaks on date)
        $dayBreakLogs = $dayLogs->filter(function ($log) {
            return in_array($log->action, ['break_start', 'break_end']);
        });
        $allBreaks = [];
        $allTotalBreakSec = 0;
        $currBreakStart = null;

        foreach ($dayBreakLogs as $blog) {
            $time = $blog->created_at->copy()->setTimezone($appTimezone);
            if ($blog->action === 'break_start') {
                $currBreakStart = $time;
            } elseif ($blog->action === 'break_end' && $currBreakStart) {
                $dur = $time->timestamp - $currBreakStart->timestamp;
                if ($dur < 0) $dur = 0;
                $allTotalBreakSec += $dur;
                $allBreaks[] = [
                    'start' => $currBreakStart,
                    'end' => $time,
                    'duration' => $dur
                ];
                $currBreakStart = null;
            }
        }
        if ($currBreakStart && $date->isToday()) {
            $timeNow = now()->setTimezone($appTimezone);
            $dur = $timeNow->timestamp - $currBreakStart->timestamp;
            if ($dur < 0) $dur = 0;
            $allTotalBreakSec += $dur;
            $allBreaks[] = [
                'start' => $currBreakStart,
                'end' => null,
                'duration' => $dur
            ];
        }

        return [
            'date' => $date->toDateString(),
            'is_future' => false,
            'shift_intervals' => $shiftIntervals,
            'slots' => $slotsBreakdown,
            'overall' => [
                'check_in' => $overallCheckIn,
                'last_app_end' => $overallLastAppEnd,
                'first_consult_start' => $overallFirstConsult,
                'total_break_seconds' => $allTotalBreakSec,
                'active_seconds' => $overallActiveSeconds,
                'extra_seconds' => $overallExtraSeconds,
                'breaks' => $allBreaks,
            ],
        ];
    }

    // Get patient-wise consultations for the consultations tab
    public function getPatientConsultations()
    {
        $appTimezone = config('app.timezone') ?: 'UTC';
        $from = $this->logFromDate ? Carbon::parse($this->logFromDate, $appTimezone)->startOfDay() : Carbon::today($appTimezone)->startOfDay();
        $to = $this->logToDate ? Carbon::parse($this->logToDate, $appTimezone)->endOfDay() : Carbon::today($appTimezone)->endOfDay();

        $query = \App\Models\Appointment::query()
            ->whereBetween('appointment_date', [$from->toDateString(), $to->toDateString()])
            ->with(['patient', 'doctor']);

        if ($this->logDoctorId) {
            $query->where('doctor_id', $this->logDoctorId);
        }

        if ($this->logDoctorId && $this->logFromDate === $this->logToDate && !is_null($this->selectedSlotIndex)) {
            $stats = $this->getTimingStatsForDate($this->logDoctorId, $this->logFromDate);
            if (isset($stats['slots'][$this->selectedSlotIndex])) {
                $slot = $stats['slots'][$this->selectedSlotIndex];
                $slotStartStr = $slot['start']->format('H:i:s');
                $slotEndStr = $slot['end']->format('H:i:s');
                $query->whereBetween('appointment_time', [$slotStartStr, $slotEndStr]);
            }
        }

        $appointments = $query->get();

        $appointmentIds = $appointments->pluck('id');
        $appLogs = \App\Models\AppointmentQueueLog::whereIn('appointment_id', $appointmentIds)
            ->orderBy('created_at', 'asc')
            ->get()
            ->groupBy('appointment_id');

        $result = [];
        foreach ($appointments as $app) {
            $logs = $appLogs->get($app->id) ?? collect([]);
            
            $checkInLog = $logs->first(fn($l) => $l->queue_status === 'checkin' || $l->action === 'revert');
            $startLog = $logs->first(fn($l) => $l->action === 'start');
            $completeLog = $logs->first(fn($l) => $l->action === 'complete');
            
            $checkInTime = $checkInLog ? $checkInLog->created_at : null;
            $startTime = $startLog ? $startLog->created_at : null;
            $completeTime = $completeLog ? $completeLog->created_at : null;

            $waitingSeconds = null;
            if ($checkInTime && $startTime) {
                $waitingSeconds = $startTime->diffInSeconds($checkInTime);
            }

            $consultSeconds = null;
            if ($startTime && $completeTime) {
                $consultSeconds = $completeTime->diffInSeconds($startTime);
            }

            $latestLog = $logs->last();
            $remarks = $latestLog ? $latestLog->remarks : null;

            $result[] = [
                'token' => $app->queue_number ?? '—',
                'patient_name' => $app->patient ? "{$app->patient->first_name} {$app->patient->last_name}" : 'Faker Patient',
                'doctor_name' => $app->doctor ? "Dr. {$app->doctor->first_name} {$app->doctor->last_name}" : '—',
                'phone' => $app->patient?->mobile_no ?? '—',
                'booked_time' => $app->appointment_time ? Carbon::parse($app->appointment_time)->format('H:i') : '—',
                'check_in' => $checkInTime,
                'started' => $startTime,
                'completed' => $completeTime,
                'waiting_seconds' => $waitingSeconds,
                'consult_seconds' => $consultSeconds,
                'status' => $app->queue_status ?: 'no_show',
                'remarks' => $remarks,
            ];
        }

        // Sort by queue number numeric part
        return collect($result)->sortBy(function ($app) {
            $parts = explode('-', $app['token']);
            return (int) end($parts);
        });
    }

    public function getQueueSummaryMetrics(): array
    {
        $logs = $this->getQueueLogsQuery()->get(['action']);

        $metrics = [
            'total' => $logs->count(),
            'patient' => 0,
            'break' => 0,
            'queue' => 0,
            'system' => 0,
        ];

        foreach ($logs as $log) {
            $metrics[$this->getLogCategory($log->action)]++;
        }

        return $metrics;
    }

    public function getDoctorAuditSummaries(): array
    {
        $doctorRows = $this->logDoctorId
            ? array_values(array_filter($this->getDoctors(), fn (array $doctor): bool => $doctor['id'] === $this->logDoctorId))
            : $this->getDoctors();

        $dateRange = $this->getDateRangeStrings();
        $consultations = $this->getPatientConsultations()->groupBy('doctor_name');
        $summaries = [];

        foreach ($doctorRows as $doctor) {
            $activeSeconds = 0;
            $breakSeconds = 0;
            $extraSeconds = 0;
            $firstCheckIn = null;
            $lastEnd = null;

            foreach ($dateRange as $dateStr) {
                $stats = $this->getTimingStatsForDate($doctor['id'], $dateStr);
                $overall = $stats['overall'] ?? [];

                $activeSeconds += (int) ($overall['active_seconds'] ?? 0);
                $breakSeconds += (int) ($overall['total_break_seconds'] ?? 0);
                $extraSeconds += (int) ($overall['extra_seconds'] ?? 0);

                if (($overall['check_in'] ?? null) && (!$firstCheckIn || $overall['check_in']->lt($firstCheckIn))) {
                    $firstCheckIn = $overall['check_in'];
                }

                if (($overall['last_app_end'] ?? null) && (!$lastEnd || $overall['last_app_end']->gt($lastEnd))) {
                    $lastEnd = $overall['last_app_end'];
                }
            }

            $doctorConsults = $consultations->get($doctor['name'], collect([]));
            $summaries[] = [
                'id' => $doctor['id'],
                'name' => $doctor['name'],
                'department' => $doctor['department'],
                'status' => $doctor['status'],
                'log_count_today' => $doctor['log_count_today'],
                'check_in' => $firstCheckIn?->format('H:i') ?? '—',
                'last_end' => $lastEnd?->format('H:i') ?? '—',
                'patients_attended' => $doctorConsults->where('status', 'completed')->count(),
                'skipped_count' => $doctorConsults->whereIn('status', ['skipped', 'no_show'])->count(),
                'active_time' => $this->formatDurationMinutes($activeSeconds),
                'break_time' => $this->formatDurationMinutes($breakSeconds),
                'extra_time' => $this->formatDurationMinutes($extraSeconds),
            ];
        }

        if (!$this->logDoctorId && count($summaries) > 1) {
            $summaries[] = [
                'id' => 'combined',
                'name' => 'Combined Report',
                'department' => 'All Departments',
                'status' => 'Live',
                'log_count_today' => array_sum(array_column($summaries, 'log_count_today')),
                'check_in' => '—',
                'last_end' => '—',
                'patients_attended' => array_sum(array_column($summaries, 'patients_attended')),
                'skipped_count' => array_sum(array_column($summaries, 'skipped_count')),
                'active_time' => $this->formatDurationMinutes($this->sumDurationMinutes($summaries, 'active_time')),
                'break_time' => $this->formatDurationMinutes($this->sumDurationMinutes($summaries, 'break_time')),
                'extra_time' => $this->formatDurationMinutes($this->sumDurationMinutes($summaries, 'extra_time')),
            ];
        }

        return $summaries;
    }

    public function getStatusTransition(AppointmentQueueLog $log): array
    {
        return match ($log->action) {
            'check_in' => ['old' => 'Not available', 'new' => 'Available'],
            'check_out' => ['old' => 'Available', 'new' => 'Checked out'],
            'break_start' => ['old' => 'Available', 'new' => 'On Break'],
            'break_end' => ['old' => 'On Break', 'new' => 'Available'],
            'start' => ['old' => 'Called', 'new' => 'Running'],
            'complete' => ['old' => 'Running', 'new' => 'Completed'],
            'skip' => ['old' => 'Called', 'new' => 'Skipped'],
            'revert' => ['old' => 'Skipped', 'new' => 'Queued'],
            'not_complete' => ['old' => 'Called', 'new' => 'No Show'],
            default => ['old' => '—', 'new' => '—'],
        };
    }

    public function getCurrentAuditHeading(): string
    {
        if (!$this->logDoctorId) {
            return 'All Doctors Audit Trail';
        }

        $doctor = Doctor::find($this->logDoctorId);

        return $doctor ? "Dr. {$doctor->first_name} {$doctor->last_name} Audit Trail" : 'Doctor Audit Trail';
    }

    public function getCurrentDoctorDescription(): string
    {
        if (!$this->logDoctorId) {
            return 'Clear timeline for check-in, patient calls, consultation start/end, skipped patient, breaks, extra time and status changes.';
        }

        $doctor = Doctor::with('departments')->find($this->logDoctorId);
        $department = $doctor?->departments?->first()?->name ?? 'General Practice';

        return "{$department} queue audit with patient flow, break actions, consultation timing and operator history.";
    }

    // Get formatted log details for Timeline UI
    public function getLogDetails(AppointmentQueueLog $log): array
    {
        $appTimezone = config('app.timezone') ?: 'UTC';
        $action = $log->action;
        $title = ucfirst(str_replace('_', ' ', $action));
        $desc = '';
        $color = 'gray'; // default color
        $durationText = '';
        $timeRange = '';

        $patientName = $log->appointment?->patient 
            ? $log->appointment->patient->first_name . ' ' . $log->appointment->patient->last_name 
            : ($log->appointment ? 'Faker Patient' : 'Patient');
        $queueNo = $log->appointment?->queue_number ?: '';

        switch ($action) {
            case 'check_in':
                $title = 'Checked In';
                $color = 'green';
                $desc = 'Doctor checked in and is available for queue management.';
                $timeRange = $log->created_at->copy()->setTimezone($appTimezone)->format('H:i');
                break;

            case 'check_out':
                $title = 'Checked Out';
                $color = 'danger';
                $checkInLog = AppointmentQueueLog::where('doctor_id', $log->doctor_id)
                    ->where('action', 'check_in')
                    ->where('created_at', '<', $log->created_at)
                    ->latest()
                    ->first();
                if ($checkInLog) {
                    $diff = $log->created_at->diffInSeconds($checkInLog->created_at);
                    $durationText = $this->formatDuration($diff);
                    $desc = "Checked out. Active Session: " . $checkInLog->created_at->copy()->setTimezone($appTimezone)->format('H:i') . " - " . $log->created_at->copy()->setTimezone($appTimezone)->format('H:i');
                } else {
                    $desc = 'Doctor checked out.';
                }
                $timeRange = $log->created_at->copy()->setTimezone($appTimezone)->format('H:i');
                break;

            case 'break_start':
                $title = 'Went on Break';
                $color = 'amber';
                $desc = 'Doctor went on break.';
                $timeRange = $log->created_at->copy()->setTimezone($appTimezone)->format('H:i');
                break;

            case 'break_end':
                $title = 'Returned from Break';
                $color = 'green';
                $breakStartLog = AppointmentQueueLog::where('doctor_id', $log->doctor_id)
                    ->where('action', 'break_start')
                    ->where('created_at', '<', $log->created_at)
                    ->latest()
                    ->first();
                if ($breakStartLog) {
                    $diff = $log->created_at->diffInSeconds($breakStartLog->created_at);
                    $durationText = $this->formatDuration($diff);
                    $desc = "Returned to queue. Break Session: " . $breakStartLog->created_at->copy()->setTimezone($appTimezone)->format('H:i') . " - " . $log->created_at->copy()->setTimezone($appTimezone)->format('H:i');
                } else {
                    $desc = 'Returned to queue from break.';
                }
                $timeRange = $log->created_at->copy()->setTimezone($appTimezone)->format('H:i');
                break;

            case 'start':
                $title = 'Consultation Started';
                $color = 'indigo';
                if ($log->duration_seconds) {
                    $durationText = $this->formatDuration(abs($log->duration_seconds));
                    $end = $log->ended_at ?: $log->created_at->addSeconds($log->duration_seconds);
                    $desc = "Consultation for {$patientName} " . ($queueNo ? "({$queueNo})" : "") . " completed. Session: " . $log->created_at->copy()->setTimezone($appTimezone)->format('H:i') . " - " . $end->copy()->setTimezone($appTimezone)->format('H:i');
                } else {
                    $desc = "Consultation started for {$patientName} " . ($queueNo ? "({$queueNo})" : "") . ".";
                }
                $timeRange = $log->created_at->copy()->setTimezone($appTimezone)->format('H:i');
                break;

            case 'complete':
                $title = 'Consultation Completed';
                $color = 'green';
                if ($log->duration_seconds) {
                    $durationText = $this->formatDuration(abs($log->duration_seconds));
                    $start = $log->started_at ?: $log->created_at->subSeconds($log->duration_seconds);
                    $desc = "Completed consultation for {$patientName} " . ($queueNo ? "({$queueNo})" : "") . ". Session: " . $start->copy()->setTimezone($appTimezone)->format('H:i') . " - " . $log->created_at->copy()->setTimezone($appTimezone)->format('H:i');
                } else {
                    $desc = "Completed consultation for {$patientName} " . ($queueNo ? "({$queueNo})" : "") . ".";
                }
                $timeRange = $log->created_at->copy()->setTimezone($appTimezone)->format('H:i');
                break;

            case 'skip':
                $title = 'Patient Skipped';
                $color = 'gray';
                $desc = "Patient {$patientName} " . ($queueNo ? "({$queueNo})" : "") . " was skipped.";
                $timeRange = $log->created_at->copy()->setTimezone($appTimezone)->format('H:i');
                break;

            case 'not_complete':
                $title = 'Marked as No Show';
                $color = 'danger';
                $desc = "Patient {$patientName} " . ($queueNo ? "({$queueNo})" : "") . " marked as No Show.";
                $timeRange = $log->created_at->copy()->setTimezone($appTimezone)->format('H:i');
                break;

            case 'revert':
                $title = 'Patient Checked In';
                $color = 'green';
                $desc = "Patient {$patientName} " . ($queueNo ? "({$queueNo})" : "") . " checked in.";
                $timeRange = $log->created_at->copy()->setTimezone($appTimezone)->format('H:i');
                break;
        }

        return [
            'title' => $title,
            'desc' => $desc,
            'color' => $color,
            'duration' => $durationText,
            'time_range' => $timeRange,
            'remarks' => $log->remarks,
        ];
    }

    // Download Queue Logs as CSV
    public function downloadLogs()
    {
        $doctorId = $this->logDoctorId;
        if (!$doctorId) {
            $doctors = Doctor::active()->get();
        } else {
            $doctors = Doctor::where('id', $doctorId)->get();
        }

        $from = $this->logFromDate ? Carbon::parse($this->logFromDate)->startOfDay() : Carbon::today()->startOfDay();
        $to = $this->logToDate ? Carbon::parse($this->logToDate)->endOfDay() : Carbon::today()->endOfDay();
        $appTimezone = config('app.timezone') ?: 'UTC';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="queue_report_' . now()->format('Y-m-d_H-i-s') . '.csv"',
        ];

        $callback = function () use ($doctors, $from, $to, $doctorId, $appTimezone) {
            $file = fopen('php://output', 'w');
            
            // 1. Write Doctor Summaries Section
            fputcsv($file, ['DOCTOR DAILY SHIFT & TIMING SUMMARY']);
            fputcsv($file, [
                'Date',
                'Doctor Name',
                'Department',
                'Room',
                'Scheduled Shift Start',
                'Scheduled Shift End',
                'Doctor Check-in',
                'Last Appointment End',
                'Actual Active Time',
                'Total Break Time',
                'Extra Time'
            ]);

            $dateRange = [];
            $currentDate = $from->copy();
            while ($currentDate->lte($to)) {
                $dateRange[] = $currentDate->toDateString();
                $currentDate->addDay();
            }

            foreach ($doctors as $doc) {
                foreach ($dateRange as $dateStr) {
                    $stats = $this->getTimingStatsForDate($doc->id, $dateStr);
                    
                    $deptName = $doc->departments->first()?->name ?? 'General Practice';
                    $room = $doc->address_line2 ?: 'Room ' . rand(101, 305);
                    
                    fputcsv($file, [
                        Carbon::parse($dateStr)->format('d/m/Y'),
                        "Dr. {$doc->first_name} {$doc->last_name}",
                        $deptName,
                        $room,
                        count($stats['shift_intervals']) ? explode(' - ', $stats['shift_intervals'][0])[0] : '—',
                        count($stats['shift_intervals']) ? explode(' - ', $stats['shift_intervals'][count($stats['shift_intervals']) - 1])[1] : '—',
                        $stats['overall']['check_in'] ? $stats['overall']['check_in']->format('H:i') : '—',
                        $stats['overall']['last_app_end'] ? $stats['overall']['last_app_end']->format('H:i') : '—',
                        $this->formatDurationMinutes((int) ($stats['overall']['active_seconds'] ?? 0)),
                        $this->formatDurationMinutes((int) ($stats['overall']['total_break_seconds'] ?? 0)),
                        $this->formatDurationMinutes((int) ($stats['overall']['extra_seconds'] ?? 0)),
                    ]);
                }
            }

            fputcsv($file, []); // Blank line separator
            fputcsv($file, []);

            // 2. Write Patient Consultations Section
            fputcsv($file, ['PATIENT-WISE CONSULTATION SUMMARY']);
            fputcsv($file, [
                'Date',
                'Doctor Name',
                'Token',
                'Patient Name',
                'Phone',
                'Booked Time',
                'Check-in Time',
                'Start Time',
                'Complete Time',
                'Waiting Time',
                'Consultation Duration',
                'Status',
                'Remarks'
            ]);

            foreach ($doctors as $doc) {
                $appointments = \App\Models\Appointment::where('doctor_id', $doc->id)
                    ->whereBetween('appointment_date', [$from, $to])
                    ->with('patient')
                    ->get();

                $appointmentIds = $appointments->pluck('id');
                $appLogs = \App\Models\AppointmentQueueLog::whereIn('appointment_id', $appointmentIds)
                    ->orderBy('created_at', 'asc')
                    ->get()
                    ->groupBy('appointment_id');

                foreach ($appointments as $app) {
                    $logs = $appLogs->get($app->id) ?? collect([]);
                    
                    $checkInLog = $logs->first(fn($l) => $l->queue_status === 'checkin' || $l->action === 'revert');
                    $startLog = $logs->first(fn($l) => $l->action === 'start');
                    $completeLog = $logs->first(fn($l) => $l->action === 'complete');
                    
                    $checkInTime = $checkInLog ? $checkInLog->created_at->copy()->setTimezone($appTimezone) : null;
                    $startTime = $startLog ? $startLog->created_at->copy()->setTimezone($appTimezone) : null;
                    $completeTime = $completeLog ? $completeLog->created_at->copy()->setTimezone($appTimezone) : null;

                    $waitingText = '—';
                    if ($checkInTime && $startTime) {
                        $waitingText = $this->formatDurationMinutes($startTime->diffInSeconds($checkInTime));
                    }

                    $consultText = '—';
                    if ($startTime && $completeTime) {
                        $consultText = $this->formatDurationMinutes($completeTime->diffInSeconds($startTime));
                    }

                    $statusText = $app->queue_status ?: 'no_show';
                    $statusLabel = match ($statusText) {
                        'no_show' => 'No Show',
                        'checkin' => 'Checked-in',
                        'started' => 'Started',
                        default => ucfirst($statusText),
                    };

                    $latestLog = $logs->last();
                    $remarks = $latestLog ? $latestLog->remarks : '—';

                    fputcsv($file, [
                        $app->appointment_date->format('d/m/Y'),
                        "Dr. {$doc->first_name} {$doc->last_name}",
                        $app->queue_number ?? '—',
                        $app->patient ? "{$app->patient->first_name} {$app->patient->last_name}" : 'Faker Patient',
                        $app->patient?->mobile_no ?? '—',
                        $app->appointment_time ? Carbon::parse($app->appointment_time)->format('H:i') : '—',
                        $checkInTime ? $checkInTime->format('H:i') : '—',
                        $startTime ? $startTime->format('H:i') : '—',
                        $completeTime ? $completeTime->format('H:i') : '—',
                        $waitingText,
                        $consultText,
                        $statusLabel,
                        $remarks ?? '—',
                    ]);
                }
            }

            fputcsv($file, []); // Blank line separator
            fputcsv($file, []);

            // 3. Write Detailed Audit Log Section
            fputcsv($file, ['DETAILED AUDIT TRAIL LOGS']);
            fputcsv($file, [
                'Log ID',
                'Date & Time',
                'Doctor Name',
                'Action Type',
                'Old Status',
                'New Status',
                'Performed By',
                'Remarks / Note',
                'Notification Sent'
            ]);

            $query = \App\Models\AppointmentQueueLog::query()
                ->with(['appointment.patient', 'creator', 'doctor'])
                ->whereBetween('created_at', [$from, $to]);

            if ($doctorId) {
                $query->where('doctor_id', $doctorId);
            }

            $logs = $query->orderBy('created_at', 'desc')->get();

            foreach ($logs as $log) {
                $details = $this->getLogDetails($log);
                $docName = $log->doctor ? "Dr. {$log->doctor->first_name} {$log->doctor->last_name}" : '—';
                
                $notificationSent = in_array($log->action, ['check_in', 'start', 'complete', 'skip']) ? 'Yes' : 'No';

                $fromStatus = '—';
                $toStatus = '—';
                if ($log->appointment_id) {
                    switch ($log->action) {
                        case 'check_in':
                        case 'revert':
                            $fromStatus = 'no_show';
                            $toStatus = 'checkin';
                            break;
                        case 'start':
                            $fromStatus = 'checkin';
                            $toStatus = 'started';
                            break;
                        case 'complete':
                            $fromStatus = 'started';
                            $toStatus = 'completed';
                            break;
                        case 'skip':
                            $fromStatus = 'checkin';
                            $toStatus = 'skipped';
                            break;
                        case 'not_complete':
                            $fromStatus = 'checkin';
                            $toStatus = 'no_show';
                            break;
                    }
                }

                fputcsv($file, [
                    $log->id,
                    $log->created_at->copy()->setTimezone($appTimezone)->format('d/m/Y H:i'),
                    $docName,
                    $details['title'],
                    $fromStatus,
                    $toStatus,
                    $log->creator ? $log->creator->name : 'System',
                    $log->remarks ?? '—',
                    $notificationSent
                ]);
            }

            fclose($file);
        };

        return response()->streamDownload($callback, 'queue_logs_' . now()->format('Y-m-d_H-i-s') . '.csv', $headers);
    }

    private function formatDuration(int $seconds): string
    {
        $hours = floor($seconds / 3600);
        $minutes = floor(($seconds / 60) % 60);
        $seconds = $seconds % 60;
        
        $parts = [];
        if ($hours > 0) $parts[] = "{$hours}h";
        if ($minutes > 0) $parts[] = "{$minutes}m";
        if ($seconds > 0 || empty($parts)) $parts[] = "{$seconds}s";
        
        return implode(' ', $parts);
    }

    protected function getActionsForLogType(string $type): ?array
    {
        return match ($type) {
            'patient' => ['start', 'complete'],
            'break' => ['break_start', 'break_end'],
            'queue' => ['skip', 'revert', 'not_complete'],
            'system' => ['check_in', 'check_out'],
            default => null,
        };
    }

    protected function getLogCategory(string $action): string
    {
        return match ($action) {
            'start', 'complete' => 'patient',
            'break_start', 'break_end' => 'break',
            'skip', 'revert', 'not_complete' => 'queue',
            default => 'system',
        };
    }

    protected function getDoctorLiveStatus(string $doctorId): string
    {
        $latestLog = AppointmentQueueLog::where('doctor_id', $doctorId)
            ->whereDate('created_at', Carbon::today())
            ->latest('created_at')
            ->first();

        if (!$latestLog) {
            return 'Live';
        }

        return match ($latestLog->action) {
            'break_start' => 'On Break',
            'check_out' => 'Checked Out',
            default => 'In OPD',
        };
    }

    protected function getDateRangeStrings(): array
    {
        $from = Carbon::parse($this->logFromDate)->startOfDay();
        $to = Carbon::parse($this->logToDate)->startOfDay();
        $dates = [];

        while ($from->lte($to)) {
            $dates[] = $from->toDateString();
            $from->addDay();
        }

        return $dates;
    }

    protected function sumDurationMinutes(array $rows, string $key): int
    {
        $seconds = 0;

        foreach ($rows as $row) {
            [$hours, $minutes] = array_pad(explode('h', str_replace('m', '', str_replace(' ', '', $row[$key]))), 2, null);

            if ($minutes === null) {
                $seconds += ((int) $hours) * 60;
                continue;
            }

            $seconds += (((int) $hours) * 60) + ((int) $minutes);
        }

        return $seconds * 60;
    }
}
