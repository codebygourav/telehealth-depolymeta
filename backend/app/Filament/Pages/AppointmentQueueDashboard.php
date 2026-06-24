<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use App\Models\Doctor;
use App\Models\Appointment;
use App\Models\AppointmentQueueLog;
use App\Traits\HasCustomSidebar;
use Carbon\Carbon;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;

class AppointmentQueueDashboard extends Page
{
    use HasCustomSidebar;

    protected string $view = 'filament.pages.appointment-queue-dashboard';
    protected static ?string $title = 'Appointments & Queue';
    protected static ?string $slug = 'appointments-queue';

    // State properties
    public ?string $selectedDoctorId = null;
    public bool $viewingLogs = false;

    // Filters for Screen 2 (Queue List)
    public string $searchQuery = '';
    public string $statusFilter = 'all';
    public string $visitTypeFilter = 'all';

    // Doctor search in Screen 1
    public string $doctorSearchQuery = '';

    public static function getSidebarOptions(): array
    {
        return [
            'label' => 'Appointments & Queue',
            'icon'  => 'heroicon-o-queue-list',
            'sort'  => 15,
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

    public function selectDoctor(string $doctorId): void
    {
        $this->selectedDoctorId = $doctorId;
        $this->viewingLogs = false;
        $this->resetFilters();
    }

    public function deselectDoctor(): void
    {
        $this->selectedDoctorId = null;
        $this->viewingLogs = false;
        $this->resetFilters();
    }

    public function toggleLogsView(): void
    {
        $this->viewingLogs = !$this->viewingLogs;
    }

    public function resetFilters(): void
    {
        $this->searchQuery = '';
        $this->statusFilter = 'all';
        $this->visitTypeFilter = 'all';
    }

    // Toggle Doctor Check-in
    public function toggleDoctorCheckIn(string $doctorId): void
    {
        $doctor = Doctor::find($doctorId);
        if (!$doctor) return;

        $newCheckedIn = !$doctor->is_checked_in;
        
        $doctor->update([
            'is_checked_in' => $newCheckedIn,
            'checked_in_at' => $newCheckedIn ? now() : null,
            'is_on_break' => false, // reset break if checked out
        ]);

        AppointmentQueueLog::create([
            'doctor_id' => $doctor->id,
            'action' => $newCheckedIn ? 'check_in' : 'check_out',
            'created_by' => Auth::id(),
        ]);

        Notification::make()
            ->title($newCheckedIn ? 'Doctor Checked In' : 'Doctor Checked Out')
            ->body("{$doctor->first_name} {$doctor->last_name} has been successfully " . ($newCheckedIn ? 'checked in.' : 'checked out.'))
            ->success()
            ->send();
    }

    // Toggle Doctor Break
    public function toggleDoctorBreak(string $doctorId): void
    {
        $doctor = Doctor::find($doctorId);
        if (!$doctor) return;

        if (!$doctor->is_checked_in) {
            Notification::make()
                ->title('Cannot Start Break')
                ->body('Doctor must be checked in first.')
                ->warning()
                ->send();
            return;
        }

        $newBreak = !$doctor->is_on_break;
        $doctor->update(['is_on_break' => $newBreak]);

        AppointmentQueueLog::create([
            'doctor_id' => $doctor->id,
            'action' => $newBreak ? 'break_start' : 'break_end',
            'created_by' => Auth::id(),
        ]);

        Notification::make()
            ->title($newBreak ? 'Doctor on Break' : 'Doctor Back from Break')
            ->body("{$doctor->first_name} {$doctor->last_name} is " . ($newBreak ? 'now on break.' : 'back and available.'))
            ->info()
            ->send();
    }

    // Start Appointment (Waiting/Skipped -> Running)
    public function startAppointment(string $appointmentId): void
    {
        $appointment = Appointment::find($appointmentId);
        if (!$appointment) return;

        $doctor = $appointment->doctor ?? Doctor::find($appointment->doctor_id);
        if (!$doctor || !$doctor->is_checked_in) {
            Notification::make()
                ->title('Blocked')
                ->body('Doctor is not checked in.')
                ->danger()
                ->send();
            return;
        }

        if ($doctor->is_on_break) {
            Notification::make()
                ->title('Blocked')
                ->body('Doctor is currently on break.')
                ->danger()
                ->send();
            return;
        }

        // Auto-complete any other running appointments for this doctor to keep queue clean
        Appointment::where('doctor_id', $appointment->doctor_id)
            ->whereDate('appointment_date', Carbon::today())
            ->where('queue_status', 'running')
            ->where('id', '!=', $appointment->id)
            ->update(['queue_status' => 'completed']);

        $appointment->update(['queue_status' => 'running']);

        Notification::make()
            ->title('Consultation Started')
            ->body("Consultation for patient " . ($appointment->patient?->first_name ?? '') . " is now running.")
            ->success()
            ->send();
    }

    // Complete Appointment (Running -> Completed)
    public function completeAppointment(string $appointmentId): void
    {
        $appointment = Appointment::find($appointmentId);
        if (!$appointment) return;

        $doctor = $appointment->doctor ?? Doctor::find($appointment->doctor_id);
        if (!$doctor || !$doctor->is_checked_in) {
            Notification::make()
                ->title('Blocked')
                ->body('Doctor is not checked in.')
                ->danger()
                ->send();
            return;
        }

        if ($doctor->is_on_break) {
            Notification::make()
                ->title('Blocked')
                ->body('Doctor is currently on break.')
                ->danger()
                ->send();
            return;
        }

        $appointment->update(['queue_status' => 'completed']);

        Notification::make()
            ->title('Consultation Completed')
            ->body("Consultation marked as completed.")
            ->success()
            ->send();
    }

    // Not Complete Appointment (Running -> Not Completed)
    public function notCompleteAppointment(string $appointmentId): void
    {
        $appointment = Appointment::find($appointmentId);
        if (!$appointment) return;

        $appointment->update(['queue_status' => 'not_completed']);

        Notification::make()
            ->title('Consultation Not Completed')
            ->body("Consultation marked as not completed.")
            ->danger()
            ->send();
    }

    // Skip Appointment (Waiting/Running -> Skipped)
    public function skipAppointment(string $appointmentId): void
    {
        $appointment = Appointment::find($appointmentId);
        if (!$appointment) return;

        $appointment->update(['queue_status' => 'skipped']);

        Notification::make()
            ->title('Patient Skipped')
            ->body("Patient marked as skipped.")
            ->warning()
            ->send();
    }

    // Revert Appointment (Any -> Waiting)
    public function revertAppointment(string $appointmentId): void
    {
        $appointment = Appointment::find($appointmentId);
        if (!$appointment) return;

        $appointment->update(['queue_status' => 'waiting']);

        Notification::make()
            ->title('Status Reverted')
            ->body("Status reverted back to Waiting.")
            ->info()
            ->send();
    }

    // Get list of doctors with stats for Screen 1
    public function getDoctors(): array
    {
        $today = Carbon::today()->toDateString();
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
            // Stats counts today
            $appointments = Appointment::where('doctor_id', $doctor->id)
                ->whereDate('appointment_date', $today)
                ->get();

            $waiting = $appointments->where('queue_status', 'waiting')->count();
            $running = $appointments->where('queue_status', 'running')->count();
            $done = $appointments->where('queue_status', 'completed')->count();

            $initials = strtoupper(substr($doctor->first_name ?? 'D', 0, 1) . substr($doctor->last_name ?? '', 0, 1));
            $dept = $doctor->departments->first()?->name ?? 'General Practice';

            $result[] = [
                'id' => $doctor->id,
                'name' => "Dr. {$doctor->first_name} {$doctor->last_name}",
                'initials' => $initials,
                'department' => $dept,
                'room' => $doctor->address_line2 ?: 'Room ' . rand(101, 305),
                'waiting' => $waiting,
                'running' => $running,
                'done' => $done,
                'is_checked_in' => (bool)$doctor->is_checked_in,
                'is_on_break' => (bool)$doctor->is_on_break,
            ];
        }

        return $result;
    }

    // Get selected doctor details
    public function getSelectedDoctor()
    {
        if (!$this->selectedDoctorId) return null;
        return Doctor::with('departments')->find($this->selectedDoctorId);
    }

    // Get stats for Screen 2
    public function getDoctorStats(): array
    {
        if (!$this->selectedDoctorId) return [];

        $today = Carbon::today()->toDateString();
        $appointments = Appointment::where('doctor_id', $this->selectedDoctorId)
            ->whereDate('appointment_date', $today)
            ->get();

        return [
            'total' => $appointments->count(),
            'waiting' => $appointments->where('queue_status', 'waiting')->count(),
            'running' => $appointments->where('queue_status', 'running')->count(),
            'completed' => $appointments->where('queue_status', 'completed')->count(),
            'skipped' => $appointments->where('queue_status', 'skipped')->count(),
            'not_completed' => $appointments->where('queue_status', 'not_completed')->count(),
        ];
    }

    // Get appointments list for Screen 2
    public function getAppointments()
    {
        if (!$this->selectedDoctorId) return collect([]);

        $today = Carbon::today()->toDateString();
        $query = Appointment::where('doctor_id', $this->selectedDoctorId)
            ->whereDate('appointment_date', $today)
            ->with('patient');

        // Apply filters
        if ($this->statusFilter !== 'all') {
            $query->where('queue_status', $this->statusFilter);
        }

        if ($this->visitTypeFilter !== 'all') {
            $query->where('consultation_type', $this->visitTypeFilter);
        }

        if (!empty($this->searchQuery)) {
            $query->whereHas('patient', function ($q) {
                $q->where('first_name', 'like', '%' . $this->searchQuery . '%')
                  ->orWhere('last_name', 'like', '%' . $this->searchQuery . '%')
                  ->orWhere('mobile_no', 'like', '%' . $this->searchQuery . '%');
            });
        }

        // Sort by queue number numeric part
        return $query->get()->sortBy(function ($app) {
            $parts = explode('-', $app->queue_number);
            return (int) end($parts);
        });
    }

    // Get Next In Queue text
    public function getNextInQueueText($currentAppointment, $allAppointments): string
    {
        if ($currentAppointment->queue_status === 'completed') {
            // Find next
            $next = $allAppointments->where('queue_status', 'waiting')->first();
            return $next ? $next->queue_number : '—';
        }

        if ($currentAppointment->queue_status === 'running') {
            $next = $allAppointments->where('queue_status', 'waiting')->first();
            return $next ? "Next: " . $next->queue_number : '—';
        }

        if ($currentAppointment->queue_status === 'waiting') {
            // Find running
            $running = $allAppointments->where('queue_status', 'running')->first();
            if ($running) {
                return "After " . $running->queue_number;
            }
            // If none is running, check if they are the first waiting
            $firstWaiting = $allAppointments->where('queue_status', 'waiting')->first();
            if ($firstWaiting && $firstWaiting->id === $currentAppointment->id) {
                return "Ready to Start";
            }
            return "In Queue";
        }

        if ($currentAppointment->queue_status === 'skipped') {
            return "Can re-queue after current";
        }

        return '—';
    }

    // Get queue action logs for Screen 3
    public function getQueueLogs()
    {
        if (!$this->selectedDoctorId) return collect([]);

        return AppointmentQueueLog::where('doctor_id', $this->selectedDoctorId)
            ->with(['appointment.patient', 'creator'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);
    }

    // Get formatted log details for Screen 3 (Audit timeline)
    public function getLogDetails(AppointmentQueueLog $log): array
    {
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
                $timeRange = $log->created_at->format('h:i A');
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
                    $desc = "Checked out. Active Session: " . $checkInLog->created_at->format('h:i A') . " - " . $log->created_at->format('h:i A');
                } else {
                    $desc = 'Doctor checked out.';
                }
                $timeRange = $log->created_at->format('h:i A');
                break;

            case 'break_start':
                $title = 'Went on Break';
                $color = 'amber';
                $desc = 'Doctor went on break.';
                $timeRange = $log->created_at->format('h:i A');
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
                    $desc = "Returned to queue. Break Session: " . $breakStartLog->created_at->format('h:i A') . " - " . $log->created_at->format('h:i A');
                } else {
                    $desc = 'Returned to queue from break.';
                }
                $timeRange = $log->created_at->format('h:i A');
                break;

            case 'start':
                $title = 'Consultation Started';
                $color = 'indigo';
                if ($log->duration_seconds) {
                    $durationText = $this->formatDuration(abs($log->duration_seconds));
                    $end = $log->ended_at ?: $log->created_at->addSeconds($log->duration_seconds);
                    $desc = "Consultation for {$patientName} " . ($queueNo ? "({$queueNo})" : "") . " completed. Session: " . $log->created_at->format('h:i A') . " - " . $end->format('h:i A');
                } else {
                    $desc = "Consultation started for {$patientName} " . ($queueNo ? "({$queueNo})" : "") . ".";
                }
                $timeRange = $log->created_at->format('h:i A');
                break;

            case 'complete':
                $title = 'Consultation Completed';
                $color = 'green';
                if ($log->duration_seconds) {
                    $durationText = $this->formatDuration(abs($log->duration_seconds));
                    $start = $log->started_at ?: $log->created_at->subSeconds($log->duration_seconds);
                    $desc = "Completed consultation for {$patientName} " . ($queueNo ? "({$queueNo})" : "") . ". Session: " . $start->format('h:i A') . " - " . $log->created_at->format('h:i A');
                } else {
                    $desc = "Completed consultation for {$patientName} " . ($queueNo ? "({$queueNo})" : "") . ".";
                }
                $timeRange = $log->created_at->format('h:i A');
                break;

            case 'skip':
                $title = 'Patient Skipped';
                $color = 'gray';
                $desc = "Patient {$patientName} " . ($queueNo ? "({$queueNo})" : "") . " was skipped.";
                $timeRange = $log->created_at->format('h:i A');
                break;

            case 'not_complete':
                $title = 'Marked as No Show';
                $color = 'danger';
                $desc = "Patient {$patientName} " . ($queueNo ? "({$queueNo})" : "") . " marked as No Show.";
                $timeRange = $log->created_at->format('h:i A');
                break;

            case 'revert':
                $title = 'Re-Queued Patient';
                $color = 'amber';
                $desc = "Patient {$patientName} " . ($queueNo ? "({$queueNo})" : "") . " was reverted back to Waiting.";
                $timeRange = $log->created_at->format('h:i A');
                break;
        }

        return [
            'title' => $title,
            'desc' => $desc,
            'color' => $color,
            'duration' => $durationText,
            'time_range' => $timeRange,
        ];
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
}
