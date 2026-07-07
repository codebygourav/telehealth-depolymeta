<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use App\Models\Doctor;
use App\Models\Appointment;
use App\Models\AppointmentQueueLog;
use App\Services\AppointmentQueueService;
use App\Traits\HasCustomSidebar;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;
use Livewire\WithPagination;

class AppointmentQueueDashboard extends Page
{
    use HasCustomSidebar;
    use WithPagination;

    protected string $view = 'filament.pages.appointment-queue-dashboard';
    protected static ?string $title = 'Appointments & Queue';
    protected static ?string $slug = 'appointments-queue';

    // State properties
    public ?string $selectedDoctorId = null;

    // Filters for Screen 2 (Queue List)
    public string $searchQuery = '';
    public string $statusFilter = 'all';
    public string $visitTypeFilter = 'all';

    // Doctor search in Screen 1
    public string $doctorSearchQuery = '';

    // Inline queue logs view state
    public bool $showLogs = false;

    // Modal state properties
    public ?string $modalAppointmentId = null;
    public string $modalRemarks = '';
    public ?string $activeModal = null;

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

    protected function getHeaderActions(): array
    {
        return [
            Action::make('back_to_doctors_list')
                ->label('Back to Doctors List')
                ->icon('heroicon-o-arrow-left')
                ->color('primary')
                ->visible(fn(): bool => filled($this->selectedDoctorId))
                ->action('deselectDoctor'),
        ];
    }

    public function selectDoctor(string $doctorId): void
    {
        $this->selectedDoctorId = $doctorId;
        $this->showLogs = false;
        $this->resetPage();
        $this->resetFilters();
    }

    public function deselectDoctor(): void
    {
        $this->selectedDoctorId = null;
        $this->showLogs = false;
        $this->resetPage();
        $this->resetFilters();
    }

    public function resetFilters(): void
    {
        $this->searchQuery = '';
        $this->statusFilter = 'all';
        $this->visitTypeFilter = 'all';
        $this->resetPage();
    }

    public function updatedSearchQuery(): void
    {
        $this->resetPage();
    }

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updatedVisitTypeFilter(): void
    {
        $this->resetPage();
    }

    public function mount(): void
    {
        if (Auth::user()?->hasRole('doctor')) {
            $doc = Doctor::where('user_id', Auth::id())->first();
            if ($doc) {
                $this->selectedDoctorId = $doc->id;
            }
        }
    }

    public function toggleLogsView(): void
    {
        if (!$this->selectedDoctorId) {
            return;
        }

        $this->showLogs = !$this->showLogs;
    }

    // Check In Doctor
    public function checkInDoctor(string $doctorId): void
    {
        $doctor = Doctor::find($doctorId);
        if (!$doctor) return;

        $doctor->update([
            'is_checked_in' => true,
            'checked_in_at' => now(),
            'is_on_break' => false,
        ]);

        AppointmentQueueLog::create([
            'doctor_id' => $doctor->id,
            'action' => 'check_in',
            'created_by' => Auth::id(),
        ]);

        Notification::make()
            ->title('Doctor Checked In')
            ->body("{$doctor->first_name} {$doctor->last_name} has been successfully checked in.")
            ->success()
            ->send();
    }

    // Check Out Doctor
    public function checkOutDoctor(string $doctorId): void
    {
        $doctor = Doctor::find($doctorId);
        if (!$doctor) return;

        $doctor->update([
            'is_checked_in' => false,
            'checked_in_at' => null,
            'is_on_break' => false,
        ]);

        AppointmentQueueLog::create([
            'doctor_id' => $doctor->id,
            'action' => 'check_out',
            'created_by' => Auth::id(),
        ]);

        Notification::make()
            ->title('Doctor Checked Out')
            ->body("{$doctor->first_name} {$doctor->last_name} has been successfully checked out.")
            ->success()
            ->send();
    }

    // Toggle Doctor Check-In (wrapper for Screen 1 button)
    public function toggleDoctorCheckIn(string $doctorId): void
    {
        $doctor = Doctor::find($doctorId);
        if (!$doctor) return;

        if ($doctor->is_checked_in) {
            $this->checkOutDoctor($doctorId);
        } else {
            $this->checkInDoctor($doctorId);
        }
    }

    // Start Doctor Break
    public function startDoctorBreak(string $doctorId): void
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

        $doctor->update(['is_on_break' => true]);

        AppointmentQueueLog::create([
            'doctor_id' => $doctor->id,
            'action' => 'break_start',
            'created_by' => Auth::id(),
        ]);

        Notification::make()
            ->title('Doctor on Break')
            ->body("{$doctor->first_name} {$doctor->last_name} is now on break.")
            ->info()
            ->send();
    }

    // End Doctor Break
    public function endDoctorBreak(string $doctorId): void
    {
        $doctor = Doctor::find($doctorId);
        if (!$doctor) return;

        $doctor->update(['is_on_break' => false]);

        AppointmentQueueLog::create([
            'doctor_id' => $doctor->id,
            'action' => 'break_end',
            'created_by' => Auth::id(),
        ]);

        Notification::make()
            ->title('Doctor Back from Break')
            ->body("{$doctor->first_name} {$doctor->last_name} is back and available.")
            ->info()
            ->send();
    }

    // Modal Action Open/Close Helpers
    public function openSkipModal(string $appointmentId): void
    {
        $this->modalAppointmentId = $appointmentId;
        $this->modalRemarks = '';
        $this->activeModal = 'skip';
    }

    public function openNoShowModal(string $appointmentId): void
    {
        $this->modalAppointmentId = $appointmentId;
        $this->modalRemarks = '';
        $this->activeModal = 'no_show';
    }

    public function submitSkip(): void
    {
        if (!$this->modalAppointmentId) return;

        $appointment = Appointment::find($this->modalAppointmentId);
        if ($appointment) {
            $appointment->temp_remarks = $this->modalRemarks;
            $appointment->update(['queue_status' => 'skipped']);

            Notification::make()
                ->title('Patient Skipped')
                ->body("Patient marked as skipped.")
                ->warning()
                ->send();
        }

        $this->closeModal();
    }

    public function openCompleteModal(string $appointmentId): void
    {
        $this->modalAppointmentId = $appointmentId;
        $this->modalRemarks = '';
        $this->activeModal = 'complete';
    }

    public function submitComplete(): void
    {
        if (!$this->modalAppointmentId) return;

        $appointment = Appointment::find($this->modalAppointmentId);
        if ($appointment) {
            $appointment->temp_remarks = $this->modalRemarks;
            if ($appointment->queue_status === 'completed') {
                Notification::make()
                    ->title('Already Completed')
                    ->body('This consultation is already marked as completed.')
                    ->info()
                    ->send();
                $this->closeModal();
                return;
            }

            $appointment->update(['queue_status' => 'completed']);

            Notification::make()
                ->title('Consultation Completed')
                ->body("Consultation marked as completed.")
                ->success()
                ->send();
        }

        $this->closeModal();
    }

    public function submitNoShow(): void
    {
        if (!$this->modalAppointmentId) return;

        $appointment = Appointment::find($this->modalAppointmentId);
        if ($appointment) {
            $appointment->temp_remarks = $this->modalRemarks;
            $appointment->update(['queue_status' => 'no_show']);

            Notification::make()
                ->title('Marked as No Show')
                ->body('Patient marked as no show.')
                ->warning()
                ->send();
        }

        $this->closeModal();
    }

    public function closeModal(): void
    {
        $this->modalAppointmentId = null;
        $this->modalRemarks = '';
        $this->activeModal = null;
    }

    // Check In Patient
    public function markCheckIn(string $appointmentId): void
    {
        $appointment = Appointment::find($appointmentId);
        if (!$appointment) return;

        $appointment->update(['queue_status' => 'checkin']);

        Notification::make()
            ->title('Checked In')
            ->body("Patient marked as checked in.")
            ->success()
            ->send();
    }

    // Start Appointment (Checkin/Skipped -> Started)
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

        // Auto-complete any other started appointments for this doctor to keep queue clean
        Appointment::where('doctor_id', $appointment->doctor_id)
            ->whereDate('appointment_date', Carbon::today())
            ->where('queue_status', 'started')
            ->where('id', '!=', $appointment->id)
            ->update(['queue_status' => 'completed']);

        $appointment->update(['queue_status' => 'started']);

        Notification::make()
            ->title('Consultation Started')
            ->body("Consultation for patient " . ($appointment->patient?->first_name ?? '') . " is now started.")
            ->success()
            ->send();
    }

    // Revert Appointment / Re-Queue (Any -> Check-in)
    public function revertAppointment(string $appointmentId): void
    {
        $appointment = Appointment::find($appointmentId);
        if (!$appointment) return;

        $appointment->update(['queue_status' => 'checkin']);

        Notification::make()
            ->title('Status Reverted')
            ->body("Status reverted back to Checked-in.")
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

            $waiting = $appointments->where('queue_status', 'checkin')->count();
            $running = $appointments->where('queue_status', 'started')->count();
            $done = $appointments->where('queue_status', 'completed')->count();
            $noShows = $appointments->where('queue_status', 'no_show')->count();

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
                'no_shows' => $noShows,
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

    public function getSelectedDoctorQueueLogs()
    {
        if (!$this->selectedDoctorId) {
            return collect([]);
        }

        return AppointmentQueueLog::query()
            ->with(['appointment.patient', 'creator', 'doctor'])
            ->where('doctor_id', $this->selectedDoctorId)
            ->orderByDesc('created_at')
            ->get();
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
            'waiting' => $appointments->where('queue_status', 'checkin')->count(),
            'started' => $appointments->where('queue_status', 'started')->count(),
            'completed' => $appointments->where('queue_status', 'completed')->count(),
            'skipped' => $appointments->where('queue_status', 'skipped')->count(),
            'no_show' => $appointments->where('queue_status', 'no_show')->count(),
        ];
    }

    protected function getAppointmentDateTime(Appointment $appointment): ?Carbon
    {
        if (!$appointment->appointment_date || !$appointment->appointment_time) {
            return null;
        }

        $date = $appointment->appointment_date instanceof Carbon
            ? $appointment->appointment_date->copy()
            : Carbon::parse($appointment->appointment_date);

        return Carbon::parse($date->toDateString() . ' ' . $appointment->appointment_time);
    }

    public function resolveQueueStatus(Appointment $appointment): string
    {
        return app(AppointmentQueueService::class)->resolveQueueStatus($appointment);
    }

    protected function isWithinScheduledActionWindow(Appointment $appointment, int $minutes = 60): bool
    {
        $appointmentDateTime = $this->getAppointmentDateTime($appointment);

        if (!$appointmentDateTime) {
            return false;
        }

        $now = Carbon::now();

        return $now->isSameDay($appointmentDateTime)
            && $now->greaterThanOrEqualTo($appointmentDateTime->copy()->subMinutes($minutes));
    }

    public function shouldShowScheduledQueueAction(Appointment $appointment): bool
    {
        return $this->resolveQueueStatus($appointment) === 'scheduled'
            && $this->isWithinScheduledActionWindow($appointment, 60);
    }

    public function getScheduledActionHint(Appointment $appointment, int $minutes = 60): string
    {
        $appointmentDateTime = $this->getAppointmentDateTime($appointment);

        if (!$appointmentDateTime) {
            return 'Actions become available 1 hour before appointment time.';
        }

        $now = Carbon::now();
        if (!$now->isSameDay($appointmentDateTime)) {
            return 'Actions are available only on the appointment day.';
        }

        $opensAt = $appointmentDateTime->copy()->subMinutes($minutes);
        if ($now->lt($opensAt)) {
            return 'Actions open at ' . $opensAt->format('h:i A') . '.';
        }

        return '';
    }

    protected function getAppointmentsQuery()
    {
        if (!$this->selectedDoctorId) {
            return Appointment::query()->whereRaw('1 = 0');
        }

        $query = app(AppointmentQueueService::class)
            ->doctorQueueQuery($this->selectedDoctorId, Carbon::today())
            ->with('patient');

        app(AppointmentQueueService::class)->applyStatusFilter($query, $this->statusFilter);

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

        return $query
            ->orderBy('appointment_time')
            ->orderBy('queue_number');
    }

    public function getAppointments()
    {
        return $this->getAppointmentsQuery()->paginate(10);
    }

    public function getAllAppointmentsForQueue()
    {
        return $this->getAppointmentsQuery()->get();
    }

    // Get Next In Queue text
    public function getNextInQueueText($currentAppointment, $allAppointments): string
    {
        return app(AppointmentQueueService::class)->getNextInQueueText($currentAppointment, $allAppointments);
    }
}
