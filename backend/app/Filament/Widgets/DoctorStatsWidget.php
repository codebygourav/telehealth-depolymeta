<?php

namespace App\Filament\Widgets;

use App\Models\Appointment;
use App\Models\Payment;
use Carbon\Carbon;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Livewire\Attributes\Reactive;

class DoctorStatsWidget extends BaseWidget
{
    #[Reactive]
    public ?array $filters = null;

    protected function getStats(): array
    {
        $doctorId = $this->filters['reportDoctor'] ?? 'all';
        $period = $this->filters['reportPeriod'] ?? 'monthly';
        $date = $this->filters['reportDate'] ?? now()->format('Y-m-d');
        $selectedDate = Carbon::parse($date);

        $start = match ($period) {
            'weekly' => $selectedDate->copy()->startOfWeek(),
            'monthly' => $selectedDate->copy()->startOfMonth(),
            default => $selectedDate->copy()->startOfYear(),
        };
        $end = match ($period) {
            'weekly' => $selectedDate->copy()->endOfWeek(),
            'monthly' => $selectedDate->copy()->endOfMonth(),
            default => $selectedDate->copy()->endOfYear(),
        };

        $doctorId = ($doctorId !== 'all') ? $doctorId : null;

        // Gross Revenue
        $totalRevenue = Payment::where('payments.status', 'paid')
            ->join('appointments', 'appointments.id', '=', 'payments.appointment_id')
            ->join('doctors', 'doctors.id', '=', 'appointments.doctor_id')
            ->when($doctorId, fn($q) => $q->where('appointments.doctor_id', $doctorId))
            ->whereBetween('appointments.appointment_date', [$start, $end])
            ->sum('payments.amount');

        // Total Appointments
        $totalAppointments = Appointment::query()
            ->when($doctorId, fn($q) => $q->where('doctor_id', $doctorId))
            ->whereBetween('appointment_date', [$start, $end])
            ->count();

        // Paid Appointments
        $paidAppointments = Appointment::query()
            ->when($doctorId, fn($q) => $q->where('doctor_id', $doctorId))
            ->whereBetween('appointment_date', [$start, $end])
            ->whereHas('payment', fn($q) => $q->where('status', 'paid'))
            ->count();

        // Cancelled Appointments
        $cancelledAppointments = Appointment::query()
            ->when($doctorId, fn($q) => $q->where('doctor_id', $doctorId))
            ->whereBetween('appointment_date', [$start, $end])
            ->whereIn('status', ['cancelled', 'no_show'])
            ->count();

        $paidPercent = $totalAppointments > 0 ? round(($paidAppointments / $totalAppointments) * 100) : 0;
        $cancelledPercent = $totalAppointments > 0 ? round(($cancelledAppointments / $totalAppointments) * 100) : 0;

        return [
            Stat::make('Total Revenue', '₹' . number_format($totalRevenue))
                ->description('Paid appointments')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->chart([7, 10, 13, 15, 12, 18, 24]) // Sparkline
                ->color('success'),
            Stat::make('Appointments', number_format($totalAppointments))
                ->description('Total bookings')
                ->descriptionIcon('heroicon-m-calendar-days')
                ->chart([15, 12, 18, 22, 19, 25, 30])
                ->color('info'),
            Stat::make('Paid Rate', $paidPercent . '%')
                ->description('Completed payments')
                ->descriptionIcon('heroicon-m-check-badge')
                ->chart([3, 5, 4, 8, 5, 9, 11])
                ->color('primary'),
            Stat::make('Cancelled', $cancelledPercent . '%')
                ->description('Cancellations & no-shows')
                ->descriptionIcon('heroicon-m-x-circle')
                ->chart([10, 8, 14, 5, 4, 2, 1])
                ->color('danger'),
        ];
    }
}
